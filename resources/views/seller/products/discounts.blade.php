<x-seller.product-layout title="Discounts">
    <x-slot:actions><a class="btn-soft" href="{{ route('seller.products.index') }}">All Products</a></x-slot:actions>
    <div x-data="productDiscounts(@js(['selected' => $selectedProducts->toArray(), 'type' => old('type', $discount?->type ?? 'percent'), 'value' => old('value', $discount?->value ?? ''), 'editing' => (bool) $discount]))" class="space-y-5">
        <x-seller.form-section :title="$discount ? 'Edit discount - '.$discount->product->name : 'Create product discounts'" description="Product discounts reduce listing prices. Shop vouchers are managed separately. One discount per product; edit or end the current discount before creating another.">
            @if (! $discount)
                <form action="{{ route('seller.products.discounts.index') }}" method="GET" role="search">
                    <x-seller.filter-bar>
                        <template x-for="product in selected" :key="product.id"><input type="hidden" name="product_ids[]" :value="product.id"></template>
                        <label class="min-w-40 flex-1"><span class="field-label">Find products</span><input name="search" maxlength="150" value="{{ request('search') }}" class="field-control" placeholder="Search name or SKU"></label>
                        <button class="btn-soft">Search</button>
                    </x-seller.filter-bar>
                </form>
                <p class="text-xs text-charcoal/55">Select up to 100 products. Selection is kept while searching and changing product pages.</p>
                @if ($products->isEmpty())
                    <x-seller.empty-state title="No products match" description="Try another search, or add your first product."><a class="btn-soft" href="{{ route('seller.products.create') }}">Add Product</a></x-seller.empty-state>
                @else
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($products as $product)
                            <label class="flex items-center gap-3 rounded-xl border border-line p-3">
                                <input type="checkbox" :checked="isSelected({{ $product->id }})" @change="toggle(@js(['id' => $product->id, 'name' => $product->name, 'price' => $product->price]), $event)">
                                <span class="min-w-0 text-sm"><span class="block break-words font-semibold">{{ $product->name }}</span><span class="text-charcoal/55">&#8369;{{ number_format($product->price, 2) }}@if ($product->discount && in_array($product->discount->state, ['live', 'scheduled'])) - Has {{ $product->discount->state }} discount @endif</span></span>
                            </label>
                        @endforeach
                    </div>
                    <div @click="preserveSelection($event)">{{ $products->links() }}</div>
                @endif
            @endif
            <form method="POST" action="{{ $discount ? route('seller.products.discounts.update', $discount) : route('seller.products.discounts.store') }}" class="space-y-4">
                @csrf @if ($discount) @method('PUT') @endif
                <template x-for="product in selected" :key="product.id"><input type="hidden" name="product_ids[]" :value="product.id"></template>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label><span class="field-label">Discount type *</span><select class="field-control" name="type" x-model="type"><option value="percent">Percentage</option><option value="fixed">Fixed amount (&#8369;)</option></select></label>
                    <label><span class="field-label">Value *</span><input type="number" name="value" min="0.01" :max="type === 'percent' ? 90 : 99999999.99" step="0.01" required x-model="value" class="field-control">@error('value')<p class="field-error">{{ $message }}</p>@enderror</label>
                    <label><span class="field-label">Start date / time</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $discount?->starts_at?->format('Y-m-d\\TH:i')) }}" class="field-control"><span class="mt-1 block text-xs text-charcoal/55">Blank starts immediately.</span></label>
                    <label><span class="field-label">End date / time *</span><input type="datetime-local" name="ends_at" required value="{{ old('ends_at', $discount?->ends_at?->format('Y-m-d\\TH:i')) }}" class="field-control">@error('ends_at')<p class="field-error">{{ $message }}</p>@enderror</label>
                </div>
                <p class="text-xs text-charcoal/55">Dates use {{ config('app.timezone') }}. Up to 90% off; final prices must be at least &#8369;0.01. For variants, the preview uses the cheapest active variant.</p>
                <div class="rounded-xl border border-line p-4">
                    <h3 class="font-semibold">Final price preview - <span x-text="selected.length">0</span> selected</h3>
                    <p x-show="!selected.length" class="mt-2 text-sm text-charcoal/55">Choose products to preview their sale prices.</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="product in selected" :key="product.id">
                            <div class="rounded-lg bg-light-gray p-3">
                                <div class="flex items-start justify-between gap-2"><span class="break-words text-sm font-semibold" x-text="product.name"></span><button x-show="!editing" type="button" @click="remove(product.id)" aria-label="Remove product from selection" class="text-charcoal/50">&times;</button></div>
                                <p class="text-sm"><span class="text-charcoal/50 line-through" x-text="money(product.price)"></span> <strong :class="validPrice(product) ? 'text-success' : 'text-danger'" x-text="money(finalPrice(product))"></strong></p>
                                <p x-show="!validPrice(product)" class="text-xs text-danger">Enter a valid discount. Minimum price &#8369;0.01.</p>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3"><button class="btn-primary" :disabled="busy || !canSave">{{ $discount ? 'Save discount' : 'Apply to selected products' }}</button>@if ($discount)<a class="btn-soft" href="{{ route('seller.products.discounts.index') }}">Cancel edit</a>@endif</div>
            </form>
        </x-seller.form-section>
        <x-seller.form-section title="Your discounts" description="Active, scheduled and expired discounts. Ending a discount immediately restores the regular price.">
            <form method="GET" action="{{ route('seller.products.discounts.index') }}">
                <x-seller.filter-bar>
                    <label><span class="field-label">Discount state</span><select name="state" class="field-control">@foreach (['' => 'All discounts', 'live' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired / ended'] as $value => $label)<option value="{{ $value }}" @selected(request('state', '') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <button class="btn-soft">Filter</button>
                </x-seller.filter-bar>
            </form>
            @if ($discounts->isEmpty())
                <x-seller.empty-state title="No discounts found" description="Choose products above to create a discount, or change the filter." />
            @else
                <x-seller.data-table label="Product discounts">
                    <x-slot:head><tr><th>Product</th><th>Discount</th><th>Schedule</th><th>State</th><th>Actions</th></tr></x-slot:head>
                    @foreach ($discounts as $item)
                        <tr>
                            <td data-label="Product"><div><a class="font-semibold text-primary" href="{{ route('seller.products.edit', $item->product) }}">{{ $item->product->name }}</a><div class="mt-1"><x-product-status :status="$item->product->status" /></div></div></td>
                            <td data-label="Discount"><div><p>{{ $item->label }}</p><p class="text-xs"><span class="line-through">&#8369;{{ number_format($item->product->price, 2) }}</span> to &#8369;{{ number_format($item->apply((float) $item->product->price), 2) }}</p></div></td>
                            <td data-label="Schedule"><div class="text-xs"><p>{{ $item->starts_at?->format('M j, Y H:i') ?? 'Immediately' }}</p><p>to {{ $item->ends_at?->format('M j, Y H:i') ?? 'No end date' }}</p></div></td>
                            <td data-label="State"><span @class(['rounded-full px-2 py-1 text-xs font-semibold', 'bg-success/10 text-success' => $item->state === 'live', 'bg-primary/10 text-primary' => $item->state === 'scheduled', 'bg-charcoal/10 text-charcoal' => !in_array($item->state, ['live', 'scheduled'])])>{{ match ($item->state) { 'live' => 'Active', 'scheduled' => 'Scheduled', default => 'Expired / ended' } }}</span></td>
                            <td data-label="Actions"><div class="product-actions"><a class="btn-soft" href="{{ route('seller.products.discounts.index', ['edit' => $item->id]) }}">Edit</a>@if (in_array($item->state, ['live', 'scheduled']))<form method="POST" action="{{ route('seller.products.discounts.destroy', $item) }}">@csrf @method('DELETE')<button class="btn-soft btn-soft--danger" :disabled="busy">End discount</button></form>@endif</div></td>
                        </tr>
                    @endforeach
                </x-seller.data-table>
                {{ $discounts->links() }}
            @endif
        </x-seller.form-section>
    </div>
</x-seller.product-layout>
