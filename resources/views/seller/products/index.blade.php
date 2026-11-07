<x-seller.product-layout title="All Products">
    <x-slot:actions><a class="btn-primary" href="{{ route('seller.products.create') }}">Add Product</a></x-slot:actions>
    <div x-data="productList(@js(['ids' => $products->modelKeys(), 'discounts' => route('seller.products.discounts.index')]))" class="space-y-5">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($summary as $label => $count)<x-seller.stat-card :label="$label" :value="$count" />@endforeach
        </div>
        <form method="GET" action="{{ route('seller.products.index') }}" role="search">
            <x-seller.filter-bar>
                <label class="min-w-40 flex-1"><span class="field-label">Search products or SKU</span><input class="field-control" name="search" maxlength="150" value="{{ request('search') }}" placeholder="Name or SKU"></label>
                <label><span class="field-label">Status</span><select class="field-control" name="status"><option value="">All statuses</option>@foreach (\App\Models\Product::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="field-label">Category</span><select class="field-control" name="category"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category['name'] }}" @selected(request('category') === $category['name'])>{{ $category['name'] }}</option>@endforeach</select></label>
                <label><span class="field-label">Stock level</span><select class="field-control" name="stock">@foreach (['' => 'All stock', 'in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'] as $value => $label)<option value="{{ $value }}" @selected(request('stock', '') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="field-label">Discount</span><select class="field-control" name="discount">@foreach (['' => 'Any discount', 'yes' => 'On discount', 'no' => 'Regular price'] as $value => $label)<option value="{{ $value }}" @selected(request('discount', '') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="field-label">Sort</span><select class="field-control" name="sort">@foreach (['newest' => 'Newest', 'oldest' => 'Oldest', 'name' => 'Name', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'stock' => 'Stock: low to high'] as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>{{ $label }}</option>@endforeach</select></label>
                <button class="btn-primary">Apply</button><a class="btn-soft" href="{{ route('seller.products.index') }}">Reset</a>
            </x-seller.filter-bar>
        </form>
        @if ($products->isEmpty())
            <x-seller.empty-state title="No products found" description="Add a product or adjust your search and filters."><a class="btn-primary" href="{{ route('seller.products.create') }}">Add Product</a></x-seller.empty-state>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-charcoal/60">Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} products</p>
                <form action="{{ route('seller.products.bulk') }}" method="POST" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <template x-for="id in selected" :key="id"><input type="hidden" name="product_ids[]" :value="id"></template>
                    <span class="text-xs"><span x-text="selected.length">0</span> selected</span>
                    <button class="btn-soft" name="action" value="deactivate" :disabled="!selected.length || busy">Deactivate</button>
                    <button class="btn-soft" type="button" :disabled="!selected.length || busy" @click="discountSelected()">Apply discount</button>
                    <button class="btn-soft btn-soft--danger" name="action" value="delete" :disabled="!selected.length || busy">Delete</button>
                </form>
            </div>
            <x-seller.data-table label="All products">
                <x-slot:head><tr>
                    <th><input type="checkbox" aria-label="Select all products on this page" @change="toggleAll($event)" :checked="selected.length === {{ $products->count() }}"></th>
                    <th>Product</th><th>SKU</th><th>Price / stock</th><th>Discount</th><th>Status</th><th>Actions</th>
                </tr></x-slot:head>
                @foreach ($products as $product)
                    <tr>
                        <td><input type="checkbox" aria-label="Select {{ $product->name }}" value="{{ $product->id }}" x-model="selected"></td>
                        <td data-label="Product">
                            <div class="flex min-w-0 items-center gap-3">
                                @if ($product->main_image_url)<img src="{{ $product->main_image_url }}" alt="" loading="lazy" class="size-12 shrink-0 rounded-lg object-cover">@else<div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-light-gray text-xs text-charcoal/50">No photo</div>@endif
                                <div class="min-w-0"><a class="font-semibold text-primary hover:underline" href="{{ route('seller.products.edit', $product) }}">{{ $product->name }}</a><p class="text-xs text-charcoal/55">{{ $product->category }}</p>@if ($product->hasVariants())<p class="text-xs text-charcoal/55">{{ $product->variants->count() }} variants</p>@endif</div>
                            </div>
                        </td>
                        <td data-label="SKU"><span class="break-all">{{ $product->sku ?: ($product->hasVariants() ? $product->variants->pluck('sku')->filter()->join(', ') : 'None') }}</span></td>
                        <td data-label="Price / stock">
                            @if (! $product->hasVariants())
                                <form id="quick-{{ $product->id }}" method="POST" action="{{ route('seller.products.quick', $product) }}" class="grid w-full min-w-32 grid-cols-2 gap-2">
                                    @csrf @method('PATCH')
                                    <label><span class="field-label">Price (&#8369;)</span><input class="field-control" aria-label="Price for {{ $product->name }}" type="number" name="price" step="0.01" min="0" max="99999999.99" required value="{{ $product->price }}"></label>
                                    <label><span class="field-label">Stock</span><input class="field-control" aria-label="Stock for {{ $product->name }}" type="number" name="stock_quantity" min="0" max="2147483647" required value="{{ $product->stock_quantity }}"></label>
                                    <button class="btn-soft col-span-2" :disabled="busy">Save price / stock</button>
                                </form>
                            @else
                                <p>From &#8369;{{ number_format($product->price, 2) }}</p><p class="text-xs">{{ number_format($product->stock_quantity) }} in stock</p>
                            @endif
                            @if ($product->stock_quantity === 0)<span class="mt-2 inline-block rounded-full bg-danger/10 px-2 py-1 text-xs font-semibold text-danger">Out of stock</span>
                            @elseif ($product->isLowStock())<span class="mt-2 inline-block rounded-full bg-warning/10 px-2 py-1 text-xs font-semibold text-warning" title="Stock threshold: {{ $product->low_stock_threshold ?? config('shop.low_stock_threshold', 5) }}">Low stock{{ $product->lowStockVariantCount() ? ' - '.$product->lowStockVariantCount().' variant(s)' : '' }}</span>@endif
                        </td>
                        <td data-label="Discount">@if ($product->liveDiscount)<div><p class="text-xs text-charcoal/50 line-through">&#8369;{{ number_format($product->price, 2) }}</p><p class="font-semibold text-success">&#8369;{{ number_format($product->display_price, 2) }}</p><p class="text-xs">{{ $product->liveDiscount->label }}</p></div>@else<span>None</span>@endif</td>
                        <td data-label="Status"><x-product-status :status="$product->status" />@if ($product->status === 'rejected')<p class="mt-2 text-xs text-danger">{{ $product->rejection_reason ?: 'Please update your listing and resubmit.' }}</p>@endif</td>
                        <td data-label="Actions">
                            <div class="product-actions">
                                <a class="btn-soft" href="{{ route('seller.products.edit', $product) }}">{{ $product->status === 'rejected' ? 'Fix and resubmit' : 'Edit' }}</a>
                                <a class="btn-soft" href="{{ route('seller.products.discounts.index', ['product_ids' => [$product->id]]) }}">Discount</a>
                                @if (!in_array($product->status, ['pending_review', 'rejected'], true))
                                    <form method="POST" action="{{ route('seller.products.status', $product) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $product->status === 'active' ? 'archived' : 'active' }}"><button class="btn-soft" :disabled="busy">{{ $product->status === 'active' ? 'Deactivate' : 'Activate' }}</button></form>
                                @endif
                                <form method="POST" action="{{ route('seller.products.duplicate', $product) }}">@csrf<button class="btn-soft" :disabled="busy">Duplicate</button></form>
                                <form method="POST" data-confirm-delete action="{{ route('seller.products.destroy', $product) }}">@csrf @method('DELETE')<button class="btn-soft btn-soft--danger" :disabled="busy">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-seller.data-table>
            {{ $products->links() }}
        @endif
    </div>
</x-seller.product-layout>
