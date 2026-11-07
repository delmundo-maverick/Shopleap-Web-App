<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SaveProductRequest;
use App\Models\Product;
use App\Services\SellerProductImages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(array_keys(Product::STATUS_LABELS))],
            'category' => ['nullable', 'string', 'max:100'],
            'stock' => ['nullable', Rule::in(['low', 'out', 'in'])],
            'discount' => ['nullable', Rule::in(['yes', 'no'])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'name', 'price_asc', 'price_desc', 'stock'])],
        ]);
        $owned = Product::where('seller_id', $request->user()->id);
        $query = (clone $owned)->with(['images', 'variants', 'liveDiscount']);
        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(fn ($search) => $search->whereLike('name', $term)->orWhereLike('sku', $term)
                ->orWhereHas('variants', fn ($variants) => $variants->whereLike('sku', $term)));
        }
        foreach (['status', 'category'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('stock')) {
            $query->stockLevel($request->input('stock'));
        }
        if ($request->filled('discount')) {
            $request->input('discount') === 'yes' ? $query->whereHas('liveDiscount') : $query->whereDoesntHave('liveDiscount');
        }
        [$column, $direction] = match ($request->input('sort', 'newest')) {
            'oldest' => ['created_at', 'asc'],
            'name' => ['name', 'asc'],
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            'stock' => ['stock_quantity', 'asc'],
            default => ['created_at', 'desc'],
        };
        $counts = (clone $owned)->select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status');
        $summary = [
            'Total' => $counts->sum(),
            'Active' => $counts->get(Product::STATUS_ACTIVE, 0),
            'Low stock' => (clone $owned)->stockLevel('low')->count(),
            'Out of stock' => (clone $owned)->stockLevel('out')->count(),
            'Pending review' => $counts->get(Product::STATUS_PENDING, 0),
            'Rejected' => $counts->get(Product::STATUS_REJECTED, 0),
        ];

        return view('seller.products.index', [
            'products' => $query->orderBy($column, $direction)->orderBy('id')->paginate(15)->withQueryString(),
            'categories' => config('shopleap_categories.list'),
            'summary' => $summary,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new Product([
            'status' => Product::STATUS_DRAFT,
            'category' => $request->user()->sellerProfile?->line_of_business,
            'low_stock_threshold' => config('shop.low_stock_threshold', 5),
        ]));
    }

    public function edit(Request $request, Product $product): View
    {
        Gate::authorize('update', $product);

        return $this->form($product->load(['images', 'options', 'variants' => fn ($query) => $query->orderBy('id')]));
    }

    private function form(Product $product): View
    {
        return view('seller.products.create', [
            'product' => $product,
            'categories' => config('shopleap_categories.list'),
        ]);
    }

    public function store(SaveProductRequest $request, SellerProductImages $images): RedirectResponse|JsonResponse
    {
        return $this->save($request, new Product(['seller_id' => $request->user()->id]), $images);
    }

    public function update(SaveProductRequest $request, Product $product, SellerProductImages $images): RedirectResponse|JsonResponse
    {
        return $this->save($request, $product, $images);
    }

    private function save(SaveProductRequest $request, Product $product, SellerProductImages $images): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        try {
            DB::transaction(function () use ($request, $product, $data, $images): void {
                $creating = ! $product->exists;
                if (! $creating) {
                    $product->setRawAttributes(Product::whereKey($product->id)->lockForUpdate()->firstOrFail()->getAttributes(), true);
                }
                $remainingImages = $product->images()->whereNotIn('id', $request->input('remove_images', []))->count();
                if ($remainingImages + count($request->file('images', [])) > 5) {
                    throw ValidationException::withMessages(['images' => 'Keep at most five product images. Reload to see the latest photos.']);
                }
                $status = $data['status'];
                $product->requires_review = $product->requires_review || in_array($product->status, [Product::STATUS_REJECTED, Product::STATUS_PENDING], true);
                if ($product->requires_review && $status !== Product::STATUS_DRAFT) {
                    $status = Product::STATUS_PENDING;
                }
                $product->fill(collect($data)->only(['name', 'description', 'category', 'subcategory', 'sku', 'weight_kg', 'low_stock_threshold'])->all());
                $product->status = $status;
                if ($status === Product::STATUS_PENDING) {
                    $product->rejection_reason = null;
                }
                $product->price = $data['price'] ?? $product->price ?? 0;
                $product->stock_quantity = $data['stock_quantity'] ?? $product->stock_quantity ?? 0;
                $product->save();

                if ($creating && ! empty($data['variants'])) {
                    $groups = $this->normalizeOptions($data['options']);
                    $variants = $this->matchVariantsToOptions($groups, $data['variants']);
                    foreach ($groups as $name => $values) {
                        $product->options()->create(['name' => $name, 'values' => $values]);
                    }
                    foreach ($variants as $variant) {
                        $product->variants()->create([
                            'options' => $variant['options'],
                            'price' => $variant['price'] ?? 0,
                            'stock_quantity' => $variant['stock_quantity'] ?? 0,
                            'sku' => ($variant['sku'] ?? null) ?: null,
                            'weight_kg' => $variant['weight_kg'] ?? null,
                            'is_active' => $variant['is_active'],
                        ]);
                    }
                } elseif ($product->hasVariants()) {
                    $variants = $product->variants()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                    foreach ($data['variants'] as $row) {
                        $variants[$row['id']]->update([
                            'price' => $row['price'] ?? 0,
                            'stock_quantity' => $row['stock_quantity'] ?? 0,
                            'sku' => ($row['sku'] ?? null) ?: null,
                            'weight_kg' => $row['weight_kg'] ?? null,
                            'is_active' => $row['is_active'],
                        ]);
                    }
                }
                $product->syncFromVariants();
                $this->validateDiscountPrice($product, (float) $product->price);
                $images->sync($request, $product);
            });
        } catch (ValidationException $exception) {
            $images->discardUploads();
            throw $exception;
        } catch (Throwable $exception) {
            $images->discardUploads();
            report($exception);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Product could not be saved. Your form is still here; please try again.'], 503);
            }

            return back()->withInput($request->except(['images', 'variants.*.image']))
                ->with('error', 'Product could not be saved. Check your connection and try again.');
        }
        $images->finish();

        return $this->complete(redirect()->route('seller.products.edit', $product), $product->isDraft() ? 'Draft saved.' : 'Product saved.');
    }

    public function updateStatus(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $product);
        $data = $request->validate(['status' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_ARCHIVED])]]);
        DB::transaction(function () use ($product, $data): void {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($data['status'] === Product::STATUS_ACTIVE) {
                if ($product->requires_review || in_array($product->status, [Product::STATUS_PENDING, Product::STATUS_REJECTED], true)) {
                    throw ValidationException::withMessages(['status' => 'Edit this product and resubmit it for review.']);
                }
                $this->validatePublication($product);
            } elseif ($product->requires_review || in_array($product->status, [Product::STATUS_PENDING, Product::STATUS_REJECTED], true)) {
                throw ValidationException::withMessages(['status' => 'Review status cannot be changed using this action.']);
            }
            $product->update($data);
        });

        return $this->complete(back(), 'Product status updated.');
    }

    public function quickUpdate(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $product);
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);
        DB::transaction(function () use ($product, $data): void {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            abort_if($product->hasVariants(), 422, 'Edit variant prices and stock on the product page.');
            $this->validateDiscountPrice($product, (float) $data['price']);
            $product->update($data);
        });

        return $this->complete(back(), 'Price and stock updated.');
    }

    public function duplicate(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $product);
        $copy = DB::transaction(function () use ($product): Product {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail()->load('images', 'options', 'variants');
            $copy = $product->replicate();
            $copy->fill(['name' => mb_substr($product->name, 0, 140).' (copy)', 'sku' => null, 'status' => Product::STATUS_DRAFT]);
            $copy->rejection_reason = null;
            $copy->requires_review = $product->requires_review || in_array($product->status, [Product::STATUS_PENDING, Product::STATUS_REJECTED], true);
            $copy->discount_price = null;
            $copy->save();
            foreach (['images', 'options', 'variants'] as $relation) {
                foreach ($product->{$relation} as $related) {
                    $replica = $related->replicate();
                    $replica->product_id = $copy->id;
                    $replica->save();
                }
            }

            return $copy;
        });

        return $this->complete(redirect()->route('seller.products.edit', $copy), 'Product duplicated as a draft.');
    }

    public function destroy(Request $request, Product $product, SellerProductImages $images): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $product);
        $paths = DB::transaction(fn () => $this->deleteProduct(Product::whereKey($product->id)->lockForUpdate()->firstOrFail()));
        $images->deleteUnused($paths);

        return $this->complete(redirect()->route('seller.products.index'), 'Product deleted.');
    }

    public function bulk(Request $request, SellerProductImages $images): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:100'],
            'product_ids.*' => ['integer', 'distinct'],
            'action' => ['required', Rule::in(['deactivate', 'delete'])],
        ]);
        $paths = DB::transaction(function () use ($request, $data): array {
            $products = Product::where('seller_id', $request->user()->id)->whereKey($data['product_ids'])->orderBy('id')->lockForUpdate()->get();
            abort_unless($products->count() === count($data['product_ids']), 403);
            $paths = [];
            foreach ($products as $product) {
                if ($data['action'] === 'delete') {
                    $paths = [...$paths, ...$this->deleteProduct($product)];
                } elseif (in_array($product->status, [Product::STATUS_ACTIVE, Product::STATUS_ARCHIVED], true)) {
                    $product->update(['status' => Product::STATUS_ARCHIVED]);
                } else {
                    throw ValidationException::withMessages(['product_ids' => 'Only active or inactive products can be bulk deactivated.']);
                }
            }

            return $paths;
        });
        $images->deleteUnused($paths);

        return $this->complete(back(), 'Selected products updated.');
    }

    private function deleteProduct(Product $product): array
    {
        $paths = [...$product->images()->pluck('path')->all(), ...$product->variants()->pluck('image_path')->all()];
        $product->delete();

        return $paths;
    }

    private function validatePublication(Product $product): void
    {
        if (! $product->images()->exists() && ! $product->variants()->whereNotNull('image_path')->exists()) {
            throw ValidationException::withMessages(['status' => 'Add a product or variant photo before publishing.']);
        }
        if ($product->hasVariants() && ! $product->activeVariants()->exists()) {
            throw ValidationException::withMessages(['status' => 'Activate at least one variant before publishing.']);
        }
    }

    private function validateDiscountPrice(Product $product, float $price): void
    {
        $discount = $product->discount()->where('is_active', true)->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))->first();
        if ($discount && ($discount->type === 'fixed' ? round($price - (float) $discount->value, 2) : round($price * (1 - (float) $discount->value / 100), 2)) < 0.01) {
            throw ValidationException::withMessages(['price' => 'End or adjust the discount first. The final price must be at least ₱0.01.']);
        }
    }

    /**
     * @param  array<int, array{name: string, values: string}>  $options
     * @return array<string, list<string>>
     */
    private function normalizeOptions(array $options): array
    {
        $groups = [];

        foreach ($options as $option) {
            $groups[trim($option['name'])] = collect(explode(',', $option['values']))
                ->map(fn ($v) => trim($v))
                ->filter(fn (string $value): bool => $value !== '')
                ->unique()
                ->values()
                ->all();
        }

        return $groups;
    }

    /**
     * Each variant must use exactly the declared option names, with valid values,
     * and no combination may repeat. Returns variants with option keys in declared order.
     */
    private function matchVariantsToOptions(array $groups, array $variants): array
    {
        $names = array_keys($groups);
        $seen = [];
        $ordered = [];

        foreach (array_values($variants) as $i => $variant) {
            $combo = $variant['options'];

            if (count($combo) !== count($names) || array_diff($names, array_keys($combo))) {
                throw ValidationException::withMessages([
                    "variants.{$i}.options" => 'A variant does not match your option groups. Click "Generate Variant Combinations" again.',
                ]);
            }

            $normalized = [];
            foreach ($groups as $name => $values) {
                if (! is_string($combo[$name]) || ! in_array($combo[$name], $values, true)) {
                    throw ValidationException::withMessages([
                        "variants.{$i}.options" => "\"{$name}\" has a value that is not in your option list. Click \"Generate Variant Combinations\" again.",
                    ]);
                }
                $normalized[$name] = $combo[$name];
            }

            $key = json_encode(array_values($normalized));
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    "variants.{$i}.options" => 'The same combination appears twice.',
                ]);
            }
            $seen[$key] = true;

            $variant['options'] = $normalized;
            $ordered[] = $variant;
        }

        return $ordered;
    }

    private function complete(RedirectResponse $redirect, string $message): RedirectResponse|JsonResponse
    {
        $redirect->with('status', $message);

        return request()->expectsJson()
            ? response()->json(['redirect' => $redirect->getTargetUrl(), 'message' => $message])
            : $redirect;
    }
}
