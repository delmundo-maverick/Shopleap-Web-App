<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            $query->where('name', 'ilike', '%'.$request->search.'%');
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
        $isDraft = $request->input('status') === Product::STATUS_DRAFT;

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in([Product::STATUS_DRAFT, Product::STATUS_ACTIVE])],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.999'],

            // A draft can be saved before photos are ready. Publishing needs
            // at least one general image or one variant image (checked below).
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            'options' => ['nullable', 'array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'options.*.values' => ['required', 'string', 'max:500'],

            'variants' => ['nullable', 'array', 'max:100'],
            'variants.*.options' => ['required', 'array'],
            'variants.*.price' => $isDraft
                ? ['nullable', 'numeric', 'min:0']
                : ['required', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => $isDraft
                ? ['nullable', 'integer', 'min:0']
                : ['required', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:100', 'distinct'],
            'variants.*.weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.999'],
            'variants.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];

        if ($isDraft) {
            $rules['price'] = ['nullable', 'numeric', 'min:0'];
            $rules['stock_quantity'] = ['nullable', 'integer', 'min:0'];
        } else {
            $rules['price'] = ['required_without:variants', 'nullable', 'numeric', 'min:0'];
            $rules['stock_quantity'] = ['required_without:variants', 'nullable', 'integer', 'min:0'];
            $rules['options'] = ['required_with:variants', 'nullable', 'array', 'max:3'];
            $rules['variants'] = ['required_with:options', 'nullable', 'array', 'max:100'];
        }

        $validated = $request->validate($rules, [
            'variants.required_with' => 'Click "Generate Variant Combinations" and fill in each variant before saving.',
            'options.required_with' => 'Add at least one option group for your variants.',
            'options.*.name.distinct' => 'Each option needs a different name.',
            'variants.*.sku.distinct' => 'Each variant needs a different SKU.',
            'images.max' => 'You can upload up to 5 product images.',
        ]);

        if (! empty($validated['variants']) && empty($validated['options'])) {
            throw ValidationException::withMessages([
                'options' => 'Add at least one option group for your variants.',
            ]);
        }

        $sellerCategory = $request->user()->sellerProfile?->line_of_business;

        $groups = [];
        $variants = [];

        if (! empty($validated['variants'])) {
            $normalizedVariants = collect($validated['variants'])
                ->map(fn (array $variant) => [
                    ...$variant,
                    'price' => (float) ($variant['price'] ?? 0),
                    'stock_quantity' => (int) ($variant['stock_quantity'] ?? 0),
                ])
                ->all();

            $groups = $this->normalizeOptions($validated['options']);
            $variants = $this->matchVariantsToOptions($groups, $normalizedVariants);
        }

        // Selling without a general gallery is fine as long as each SKU can
        // show its own photo, so accept either kind of image when publishing.
        $hasGeneralImageUpload = collect($request->file('images', []))
            ->filter()
            ->isNotEmpty();
        $hasVariantImageUpload = collect($request->file('variants', []))
            ->contains(fn ($variant) => is_array($variant) && ! empty($variant['image']));

        if (! $isDraft && ! $hasGeneralImageUpload && ! $hasVariantImageUpload) {
            throw ValidationException::withMessages([
                'images' => 'Add at least one product photo, or a photo for at least one variant, before publishing.',
            ]);
        }

        $hasVariants = $variants !== [];
        $status = $isDraft ? Product::STATUS_DRAFT : Product::STATUS_ACTIVE;

        DB::transaction(function () use ($request, $validated, $sellerCategory, $hasVariants, $groups, $variants, $status) {
            $product = Product::create([
                'seller_id' => $request->user()->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $sellerCategory,
                'subcategory' => null,

                // With variants: "starting from" price and total stock.
                'price' => $hasVariants
                    ? collect($variants)->min('price')
                    : ($validated['price'] ?? 0),
                'stock_quantity' => $hasVariants
                    ? collect($variants)->sum('stock_quantity')
                    : ($validated['stock_quantity'] ?? 0),
                'weight_kg' => $validated['weight_kg'] ?? null,

                'status' => $status,
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

            foreach ($variants as $index => $variant) {
                $imagePath = $request->hasFile("variants.{$index}.image")
                    ? $request->file("variants.{$index}.image")->store('variant_images', 'r2_public')
                    : null;

                ProductVariant::create([
                    'product_id' => $product->id,
                    'options' => $variant['options'],
                    'price' => $variant['price'],
                    'stock_quantity' => $variant['stock_quantity'],
                    'sku' => ($variant['sku'] ?? null) ?: null,
                    'weight_kg' => $variant['weight_kg'] ?? null,
                    'image_path' => $imagePath,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('seller.products.index')
            ->with(
                'status',
                $isDraft
                    ? 'Product saved as a draft. Publish it when you are ready.'
                    : 'Product added successfully.'
            );
    }

    public function details(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $product->load(['images', 'options', 'variants' => fn ($q) => $q->orderBy('id')]);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'subcategory' => $product->subcategory,
            'price' => (float) $product->price,
            'discount_price' => $product->discount_price ? (float) $product->discount_price : null,
            'stock_quantity' => $product->stock_quantity,
            'weight_kg' => $product->weight_kg !== null ? (float) $product->weight_kg : null,
            'low_stock_variants' => $product->lowStockVariantCount((int) config('shop.low_stock_threshold', 5)),
            'status' => $product->status,
            'images' => $product->images->map(fn ($img) => ['id' => $img->id, 'url' => $img->url]),
            'options' => $product->options->map(fn ($o) => ['name' => $o->name, 'values' => $o->values]),
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
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.999'],
        ];

        // Variant products: price/stock live on the variants. Simple products: edit directly.
        $rules += $hasVariants
            ? [
                'variants' => ['required', 'array', 'min:1'],
                'variants.*.id' => ['required', 'integer'],
                'variants.*.price' => ['required', 'numeric', 'min:0'],
                'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
                'variants.*.weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.999'],
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

        if (! empty($validated['subcategory']) && ! in_array($validated['subcategory'], $allowedSubcategories, true)) {
            throw ValidationException::withMessages([
                'subcategory' => 'Choose a subcategory that belongs to the selected category.',
            ]);
        }

        DB::transaction(function () use ($product, $validated, $hasVariants) {
            // Lock order everywhere (checkout, restoreStock, here): product first, then variants.
            Product::whereKey($product->id)->lockForUpdate()->first();

            if ($hasVariants) {
                $this->applyVariantChanges($product, $validated['variants']);

                $product->update(collect($validated)->only(['name', 'description', 'category', 'subcategory', 'weight_kg'])->all());
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
                'weight_kg' => $product->weight_kg !== null ? (float) $product->weight_kg : null,
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
            'status' => ['required', Rule::in([Product::STATUS_DRAFT, Product::STATUS_ACTIVE, Product::STATUS_ARCHIVED])],
        ]);

        // A draft may be saved without photos, but it cannot be published empty.
        // Variant photos count: a product with only per-SKU images is complete.
        if ($request->status === Product::STATUS_ACTIVE
            && ! $product->images()->exists()
            && ! $product->variants()->whereNotNull('image_path')->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Add at least one product photo, or a variant photo, before publishing.',
            ]);
        }

        $product->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "{$product->name} is now {$request->status}.",
            'status' => $request->status,
        ]);
    }

    // ------------------------------------------------------------------
    // Product / variant image management
    // ------------------------------------------------------------------

    /** Add one general product image (max 5 per product). */
    public function storeImage(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'image.max' => 'Each image must be 10MB or smaller.',
        ]);

        if ($product->images()->count() >= 5) {
            throw ValidationException::withMessages([
                'image' => 'You can upload up to 5 product images.',
            ]);
        }

        $image = ProductImage::create([
            'product_id' => $product->id,
            'path' => $request->file('image')->store('product_images', 'r2_public'),
            'sort_order' => ((int) $product->images()->max('sort_order')) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product image added.',
            'image' => ['id' => $image->id, 'url' => $image->url],
            'images' => $this->imagePayload($product),
        ]);
    }

    /** Replace one general product image without touching the other images. */
    public function updateImage(Request $request, Product $product, ProductImage $image)
    {
        $this->authorizeOwnership($request, $product);
        abort_unless($image->product_id === $product->id, 404);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'image.max' => 'Each image must be 10MB or smaller.',
        ]);

        $oldPath = $image->path;
        $image->update([
            'path' => $request->file('image')->store('product_images', 'r2_public'),
        ]);

        Storage::disk('r2_public')->delete($oldPath);

        return response()->json([
            'success' => true,
            'message' => 'Product image replaced.',
            'images' => $this->imagePayload($product),
        ]);
    }

    /** Remove one general product image. Variant images are stored separately. */
    public function destroyImage(Request $request, Product $product, ProductImage $image)
    {
        $this->authorizeOwnership($request, $product);
        abort_unless($image->product_id === $product->id, 404);

        $path = $image->path;
        $image->delete();
        Storage::disk('r2_public')->delete($path);

        return response()->json([
            'success' => true,
            'message' => 'Product image removed.',
            'images' => $this->imagePayload($product),
        ]);
    }

    /** Upload or replace the single optional image for one variant. */
    public function storeVariantImage(Request $request, Product $product, ProductVariant $variant)
    {
        $this->authorizeOwnership($request, $product);
        abort_unless($variant->product_id === $product->id, 404);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'image.max' => 'Each image must be 10MB or smaller.',
        ]);

        $oldPath = $variant->image_path;
        $variant->update([
            'image_path' => $request->file('image')->store('variant_images', 'r2_public'),
        ]);

        if ($oldPath) {
            Storage::disk('r2_public')->delete($oldPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Variant image updated.',
            'image_url' => $variant->image_url,
        ]);
    }

    /** Remove one variant's image only. General images are untouched. */
    public function destroyVariantImage(Request $request, Product $product, ProductVariant $variant)
    {
        $this->authorizeOwnership($request, $product);
        abort_unless($variant->product_id === $product->id, 404);

        $path = $variant->image_path;
        $variant->update(['image_path' => null]);

        if ($path) {
            Storage::disk('r2_public')->delete($path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Variant image removed.',
            'image_url' => null,
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

        if ($rows->filter(fn ($r) => (bool) $r['is_active'])->isEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'Keep at least one variant active, or archive the whole product instead.',
            ]);
        }

        foreach ($rows as $row) {
            $existing[$row['id']]->update([
                'price' => $row['price'],
                'stock_quantity' => $row['stock_quantity'],
                'weight_kg' => $row['weight_kg'] ?? null,
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
                ->map(fn ($v) => trim($v))
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

    private function variantPayload(Product $product): array
    {
        return $product->variants->sortBy('id')->values()->map(fn ($v) => [
            'id' => $v->id,
            'options' => $v->options,
            'label' => $v->label,
            'price' => (float) $v->price,
            'stock_quantity' => $v->stock_quantity,
            'weight_kg' => $v->weight_kg !== null ? (float) $v->weight_kg : null,
            'sku' => $v->sku,
            'image_url' => $v->image_url,
            'is_active' => $v->is_active,
        ])->all();
    }

    private function imagePayload(Product $product): array
    {
        return $product->images()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $image->url,
            ])
            ->all();
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        if ($product->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this product.');
        }
    }
}
