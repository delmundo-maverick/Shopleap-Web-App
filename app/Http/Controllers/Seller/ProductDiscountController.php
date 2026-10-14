<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductDiscountController extends Controller
{
    public function edit(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $product->load(['images', 'discount']);

        return view('seller.products.discount', [
            'product' => $product,
            'discount' => $product->discount,
        ]);
    }

    /** Create or replace this product's discount. */
    public function update(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $data = $request->validate([
            'type' => ['required', Rule::in([ProductDiscount::TYPE_PERCENT, ProductDiscount::TYPE_FIXED])],
            'value' => ['required', 'numeric', 'gt:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => array_filter([
                'nullable',
                'date',
                'after:now',
                $request->filled('starts_at') ? 'after:starts_at' : null,
            ]),
        ], [
            'ends_at.after' => 'The end date must be in the future and after the start date.',
        ]);

        $value = (float) $data['value'];

        if ($data['type'] === ProductDiscount::TYPE_PERCENT && $value > ProductDiscount::MAX_PERCENT) {
            throw ValidationException::withMessages([
                'value' => 'A percentage discount cannot be more than ' . ProductDiscount::MAX_PERCENT . '%.',
            ]);
        }

        // For products with variants, products.price is the cheapest active variant,
        // so this also guarantees the fixed amount can't wipe out any variant's price.
        if ($data['type'] === ProductDiscount::TYPE_FIXED && $value >= (float) $product->price) {
            throw ValidationException::withMessages([
                'value' => 'The discount must be lower than the product price (₱' . number_format($product->price, 2) . ').',
            ]);
        }

        ProductDiscount::updateOrCreate(
            ['product_id' => $product->id],
            [
                'seller_id' => $request->user()->id,
                'type' => $data['type'],
                'value' => $data['value'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return redirect()->route('seller.products.discount.edit', $product)
            ->with('status', 'Discount saved.');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $product->discount()->delete();

        return redirect()->route('seller.products.discount.edit', $product)
            ->with('status', 'Discount removed. Buyers now see the regular price.');
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        if ($product->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this product.');
        }
    }
}
