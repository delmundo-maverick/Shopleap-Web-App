<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SaveProductDiscountRequest;
use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductDiscountController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'in:live,scheduled,expired'],
            'edit' => ['nullable', 'integer'],
            'product_ids' => ['nullable', 'array', 'max:100'],
            'product_ids.*' => ['integer', 'distinct'],
        ]);
        $owned = Product::where('seller_id', $request->user()->id);
        $discount = isset($data['edit'])
            ? ProductDiscount::where('seller_id', $request->user()->id)->with('product')->findOrFail($data['edit'])
            : null;
        $selectedIds = old('product_ids', $data['product_ids'] ?? ($discount ? [$discount->product_id] : []));
        $selectedProducts = (clone $owned)->whereKey($selectedIds)->get(['id', 'name', 'price']);
        abort_unless($selectedProducts->count() === count(array_unique($selectedIds)), 403);
        $products = (clone $owned)->with(['images', 'variants', 'discount'])
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request): void {
                $term = '%'.$request->input('search').'%';
                $query->whereLike('name', $term)->orWhereLike('sku', $term)
                    ->orWhereHas('variants', fn ($variants) => $variants->whereLike('sku', $term));
            }))->orderBy('name')->paginate(20, ['*'], 'product_page')->withQueryString();

        $discounts = ProductDiscount::where('seller_id', $request->user()->id)->with(['product.images', 'product.variants']);
        if ($request->input('state') === 'live') {
            $discounts->live();
        } elseif ($request->input('state') === 'scheduled') {
            $discounts->where('is_active', true)->where('starts_at', '>', now())
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
        } elseif ($request->input('state') === 'expired') {
            $discounts->where(fn ($query) => $query->where('is_active', false)->orWhere('ends_at', '<=', now()));
        }

        return view('seller.products.discounts', [
            'products' => $products,
            'selectedProducts' => $selectedProducts,
            'discount' => $discount,
            'discounts' => $discounts->latest()->paginate(15, ['*'], 'discount_page')->withQueryString(),
        ]);
    }

    public function store(SaveProductDiscountRequest $request): RedirectResponse|JsonResponse
    {
        return $this->save($request);
    }

    public function update(SaveProductDiscountRequest $request, ProductDiscount $discount): RedirectResponse|JsonResponse
    {
        abort_unless($discount->seller_id === $request->user()->id, 403);

        return $this->save($request, $discount);
    }

    private function save(SaveProductDiscountRequest $request, ?ProductDiscount $discount = null): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $data, $discount): void {
            $ids = $discount ? [$discount->product_id] : $data['product_ids'];
            $products = Product::where('seller_id', $request->user()->id)->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
            abort_unless($products->count() === count($ids), 403);
            foreach ($products as $product) {
                $existing = $product->discount()->lockForUpdate()->first();
                if (! $discount && $existing && $existing->is_active && (! $existing->ends_at || $existing->ends_at->isFuture())) {
                    throw ValidationException::withMessages(['product_ids' => "{$product->name} already has an active or scheduled discount. Edit or end it first."]);
                }
                $value = (float) $data['value'];
                if ($data['type'] === ProductDiscount::TYPE_PERCENT && $value > ProductDiscount::MAX_PERCENT) {
                    throw ValidationException::withMessages(['value' => 'Percentage discounts cannot exceed '.ProductDiscount::MAX_PERCENT.'%.']);
                }
                $finalPrice = $data['type'] === ProductDiscount::TYPE_PERCENT
                    ? round((float) $product->price * (1 - $value / 100), 2)
                    : round((float) $product->price - $value, 2);
                if ($finalPrice < 0.01 || $finalPrice >= (float) $product->price) {
                    throw ValidationException::withMessages(['value' => "The final price for {$product->name} must be at least ₱0.01 and lower than its original price."]);
                }
                ProductDiscount::updateOrCreate(['product_id' => $product->id], [
                    'seller_id' => $request->user()->id,
                    'type' => $data['type'],
                    'value' => $data['value'],
                    'starts_at' => $data['starts_at'] ?? null,
                    'ends_at' => $data['ends_at'],
                    'is_active' => true,
                ]);
            }
        });

        return $this->complete(redirect()->route('seller.products.discounts.index'), 'Discounts saved.');
    }

    public function destroy(Request $request, ProductDiscount $discount): RedirectResponse|JsonResponse
    {
        abort_unless($discount->seller_id === $request->user()->id, 403);
        DB::transaction(function () use ($discount): void {
            Product::whereKey($discount->product_id)->lockForUpdate()->firstOrFail();
            $discount->update(['is_active' => false, 'ends_at' => now()]);
        });

        return $this->complete(redirect()->route('seller.products.discounts.index'), 'Discount ended. Buyers now see the regular price.');
    }

    private function complete(RedirectResponse $redirect, string $message): RedirectResponse|JsonResponse
    {
        $redirect->with('status', $message);

        return request()->expectsJson()
            ? response()->json(['redirect' => $redirect->getTargetUrl(), 'message' => $message])
            : $redirect;
    }
}
