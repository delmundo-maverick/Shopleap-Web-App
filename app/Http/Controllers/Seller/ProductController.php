<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('images')
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

        $products = $query->paginate(15)->withQueryString();
        $categories = config('shopleap_categories.list');

        return view('seller.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = config('shopleap_categories.list');

        return view('seller.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string'],
            'subcategory' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'images' => ['required', 'array', 'min:1', 'max:6'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $product = Product::create([
                'seller_id' => $request->user()->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'subcategory' => $validated['subcategory'] ?? null,
                'price' => $validated['price'],
                'discount_price' => $validated['discount_price'] ?? null,
                'stock_quantity' => $validated['stock_quantity'],
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
        });

        return redirect()->route('seller.products.index')
            ->with('status', 'Product added successfully.');
    }

    /**
     * AJAX — full product details for the edit modal.
     */
    public function details(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $product->load('images');

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'subcategory' => $product->subcategory,
            'price' => (float) $product->price,
            'discount_price' => $product->discount_price ? (float) $product->discount_price : null,
            'stock_quantity' => $product->stock_quantity,
            'status' => $product->status,
            'images' => $product->images->map(fn($img) => ['id' => $img->id, 'url' => $img->url]),
        ]);
    }

    /**
     * AJAX — update core product fields from the edit modal.
     */
    public function update(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string'],
            'subcategory' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => "{$product->name} was updated successfully.",
            'product' => [
                'name' => $product->name,
                'price' => (float) $product->price,
                'discount_price' => $product->discount_price ? (float) $product->discount_price : null,
                'stock_quantity' => $product->stock_quantity,
                'status' => $product->status,
            ],
        ]);
    }

    /**
     * AJAX — toggle between active and archived.
     */
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

    private function authorizeOwnership(Request $request, Product $product): void
    {
        if ($product->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this product.');
        }
    }
}
