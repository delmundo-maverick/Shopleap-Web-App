<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $threshold = (int) config('shop.low_stock_threshold', 5);

        $query = Product::with(['images', 'variants', 'liveDiscount'])
            ->where('seller_id', $request->user()->id)
            ->latest();

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('stock') && $request->stock !== 'all') {
            $query->stockLevel($request->stock, $threshold);
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = config('shopleap_categories.list');

        return view('seller.products.index', compact('products', 'categories'));
    }

    public function create(Request $request)
    {
        $categories = config('shopleap_categories.list');
        $sellerCategory = $request->user()->sellerProfile?->line_of_business;

        return view('seller.products.create', compact('categories', 'sellerCategory'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],

            // Simple products only; with variants these are derived from the variants.
            'price' => ['required_without:variants', 'nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required_without:variants', 'nullable', 'integer', 'min:0'],

            'images' => ['required', 'array', 'min:1', 'max:6'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],

            'options' => ['required_with:variants', 'nullable', 'array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'options.*.values' => ['required', 'string', 'max:500'],

            'variants' => ['required_with:options', 'nullable', 'array', 'max:100'],
            'variants.*.options' => ['required', 'array'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:100', 'distinct'],
        ], [
            'variants.required_with' => 'Click "Generate Variant Combinations" and fill in each variant before saving.',
            'options.required_with' => 'Add at least one option group for your variants.',
            'options.*.name.distinct' => 'Each option needs a different name.',
            'variants.*.sku.distinct' => 'Each variant needs a different SKU.',
        ]);

        $sellerCategory = $request->user()->sellerProfile?->line_of_business;

        $groups = [];
        $variants = [];

        if (!empty($validated['variants'])) {
            $groups = $this->normalizeOptions($validated['options']);
            $variants = $this->matchVariantsToOptions($groups, $validated['variants']);
        }

        $hasVariants = $variants !== [];

        DB::transaction(function () use ($request, $validated, $sellerCategory, $hasVariants, $groups, $variants) {
            $product = Product::create([
                'seller_id' => $request->user()->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $sellerCategory,
                'subcategory' => null,

                // With variants: "starting from" price and total stock.
                'price' => $hasVariants
                    ? collect($variants)->min('price')
                    : $validated['price'],
                'stock_quantity' => $hasVariants
                    ? collect($variants)->sum('stock_quantity')
                    : $validated['stock_quantity'],

                'status' => 'active',
            ]);

            foreach ($request->file('images', []) as $index => $file) {
                $path = $file->store('product_images', 'r2_public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'sort_order' => $index,
                ]);
            }

            foreach ($groups as $name => $values) {
                ProductOption::create([
                    'product_id' => $product->id,
                    'name' => $name,
                    'values' => $values,
                ]);
            }

            foreach ($variants as $variant) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'options' => $variant['options'],
                    'price' => $variant['price'],
                    'stock_quantity' => $variant['stock_quantity'],
                    'sku' => $variant['sku'] ?? null,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('seller.products.index')
            ->with('status', 'Product added successfully.');
    }

    public function details(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $product->load(['images', 'options', 'variants' => fn($q) => $q->orderBy('id')]);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'subcategory' => $product->subcategory,
            'price' => (float) $product->price,
            'discount_price' => $product->discount_price ? (float) $product->discount_price : null,
            'stock_quantity' => $product->stock_quantity,
            'low_stock_variants' => $product->lowStockVariantCount((int) config('shop.low_stock_threshold', 5)),
            'status' => $product->status,
            'images' => $product->images->map(fn($img) => ['id' => $img->id, 'url' => $img->url]),
            'options' => $product->options->map(fn($o) => ['name' => $o->name, 'values' => $o->values]),
            'has_variants' => $product->variants->isNotEmpty(),
            'variants' => $this->variantPayload($product),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $categories = collect(config('shopleap_categories.list'));
        $hasVariants = $product->hasVariants();

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in($categories->pluck('name')->all())],
            'subcategory' => ['nullable', 'string', 'max:100'],
        ];

        // Variant products: price/stock live on the variants. Simple products: edit directly.
        $rules += $hasVariants
            ? [
                'variants' => ['required', 'array', 'min:1'],
                'variants.*.id' => ['required', 'integer'],
                'variants.*.price' => ['required', 'numeric', 'min:0'],
                'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
                'variants.*.sku' => ['nullable', 'string', 'max:100', 'distinct'],
                'variants.*.is_active' => ['required', 'boolean'],
            ]
            : [
                'price' => ['required', 'numeric', 'min:0'],
                'stock_quantity' => ['required', 'integer', 'min:0'],
            ];

        $validated = $request->validate($rules, [
            'variants.*.sku.distinct' => 'Each variant needs a different SKU.',
        ]);

        $allowedSubcategories = $categories->firstWhere('name', $validated['category'])['subcategories'] ?? [];

        if (!empty($validated['subcategory']) && !in_array($validated['subcategory'], $allowedSubcategories, true)) {
            throw ValidationException::withMessages([
                'subcategory' => 'Choose a subcategory that belongs to the selected category.',
            ]);
        }

        DB::transaction(function () use ($product, $validated, $hasVariants) {
            // Lock order everywhere (checkout, restoreStock, here): product first, then variants.
            Product::whereKey($product->id)->lockForUpdate()->first();

            if ($hasVariants) {
                $this->applyVariantChanges($product, $validated['variants']);

                $product->update(collect($validated)->only(['name', 'description', 'category', 'subcategory'])->all());
                $product->syncFromVariants();
            } else {
                $product->update($validated);
            }
        });

        $product->refresh()->load('variants', 'liveDiscount');
        $threshold = (int) config('shop.low_stock_threshold', 5);

        return response()->json([
            'success' => true,
            'message' => "{$product->name} was updated successfully.",
            'product' => [
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                // products.js reads this key for the sale price + strike-through.
                'discount_price' => $product->was_price ? $product->display_price : null,
                'stock_quantity' => $product->stock_quantity,
                'status' => $product->status,
                'low_stock_variants' => $product->lowStockVariantCount($threshold),
            ],
            'variants' => $this->variantPayload($product),
        ]);
    }

    public function updateStatus(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $request->validate([
            'status' => ['required', 'in:active,archived'],
        ]);

        $product->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "{$product->name} is now {$request->status}.",
            'status' => $request->status,
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Update variants by id. Never delete: carts and orders reference them. */
    private function applyVariantChanges(Product $product, array $rows): void
    {
        $existing = $product->variants()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $rows = collect($rows);

        if ($rows->pluck('id')->diff($existing->keys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'One or more variants no longer exist. Close this window and try again.',
            ]);
        }

        if ($rows->filter(fn($r) => (bool) $r['is_active'])->isEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'Keep at least one variant active, or archive the whole product instead.',
            ]);
        }

        foreach ($rows as $row) {
            $existing[$row['id']]->update([
                'price' => $row['price'],
                'stock_quantity' => $row['stock_quantity'],
                'sku' => $row['sku'] ?? null,
                'is_active' => (bool) $row['is_active'],
            ]);
        }
    }

    /** "Pack Size" => "Pack of 4, Pack of 5" becomes ['Pack Size' => ['Pack of 4', 'Pack of 5']]. */
    private function normalizeOptions(array $options): array
    {
        $groups = [];

        foreach ($options as $option) {
            $groups[trim($option['name'])] = collect(explode(',', $option['values']))
                ->map(fn($v) => trim($v))
                ->filter()
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
                if (!is_string($combo[$name]) || !in_array($combo[$name], $values, true)) {
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

    private function variantPayload(Product $product): array
    {
        return $product->variants->sortBy('id')->values()->map(fn($v) => [
            'id' => $v->id,
            'options' => $v->options,
            'label' => $v->label,
            'price' => (float) $v->price,
            'stock_quantity' => $v->stock_quantity,
            'sku' => $v->sku,
            'is_active' => $v->is_active,
        ])->all();
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        if ($product->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this product.');
        }
    }
}
