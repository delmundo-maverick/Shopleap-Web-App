@props(['product', 'categories'])
@php
    $editing = $product->exists;
    $existingVariants = $editing ? $product->variants->map(fn ($variant) => [
        'id' => $variant->id, 'key' => 'variant-'.$variant->id, 'options' => $variant->options,
        'price' => $variant->price, 'stock_quantity' => $variant->stock_quantity,
        'sku' => $variant->sku, 'weight_kg' => $variant->weight_kg, 'is_active' => (int) $variant->is_active,
        'image_url' => $variant->image_url, 'remove_image' => false,
    ])->all() : [];
    $formConfig = [
        'editing' => $editing, 'hasVariants' => count($existingVariants) > 0,
        'name' => old('name', $product->name ?? ''), 'description' => old('description', $product->description ?? ''),
        'category' => old('category', $product->category ?? ''),
        'price' => old('price', $product->price ?? ''), 'stock' => old('stock_quantity', $product->stock_quantity ?? ''),
        'categories' => $categories, 'subcategory' => old('subcategory', $product->subcategory ?? ''),
        'variants' => old('variants', $existingVariants),
        'options' => old('options', []),
        'existingImages' => $editing ? $product->images->map(fn ($image) => ['id' => $image->id, 'url' => $image->url])->all() : [],
        'removedImages' => old('remove_images', []),
    ];
@endphp
<form action="{{ $editing ? route('seller.products.update', $product) : route('seller.products.store') }}" method="POST" enctype="multipart/form-data"
    x-data="productForm(@js($formConfig))" class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="min-w-0 space-y-5">
        @if ($editing)
            <div class="flex flex-wrap items-center gap-3"><x-product-status :status="$product->status" /><p class="text-sm text-charcoal/60">Product #{{ $product->id }}</p></div>
            @if ($product->status === 'rejected')<div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"><strong>Changes requested:</strong> {{ $product->rejection_reason ?: 'Please correct the listing before resubmitting.' }}</div>@endif
        @endif
        <x-seller.form-section title="Basic information" description="Give buyers a clear name and description.">
            <div>
                <label class="field-label" for="product-name">Product name *</label>
                <input id="product-name" name="name" x-model="name" value="{{ old('name', $product->name) }}" required maxlength="150" class="field-control" aria-describedby="name-count">
                <p id="name-count" class="mt-1 text-xs text-charcoal/55"><span x-text="name.length">0</span>/150 characters</p>@error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="product-description">Description</label>
                <textarea id="product-description" name="description" x-model="description" maxlength="2000" rows="5" class="field-control" aria-describedby="description-count">{{ old('description', $product->description) }}</textarea>
                <p id="description-count" class="mt-1 text-xs text-charcoal/55"><span x-text="description.length">0</span>/2000 characters</p>@error('description')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <label><span class="field-label">Category *</span><select name="category" x-model="category" @change="subcategory = ''" required class="field-control"><option value="">Choose a category</option>@foreach ($categories as $category)<option value="{{ $category['name'] }}" @selected(old('category', $product->category) === $category['name'])>{{ $category['name'] }}</option>@endforeach</select>@error('category')<p class="field-error">{{ $message }}</p>@enderror</label>
                <label><span class="field-label">Subcategory</span><select name="subcategory" x-model="subcategory" class="field-control"><option value="">Optional</option><template x-for="choice in subcategories" :key="choice"><option :value="choice" x-text="choice"></option></template></select></label>
            </div>
            <label class="block"><span class="field-label">Product SKU</span><input name="sku" maxlength="100" value="{{ old('sku', $product->sku) }}" class="field-control" placeholder="Optional internal reference"></label>
        </x-seller.form-section>
        <x-seller.form-section title="Images" description="Upload JPG, PNG or WebP, up to 10 MB each and 5 general photos. Use clear images at least 800 x 800 px when possible. The first image becomes the cover.">
            <template x-if="existingImages.length">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <template x-for="image in existingImages" :key="image.id">
                        <label class="rounded-xl border border-line p-2" :class="removedImages.includes(String(image.id)) ? 'opacity-40' : ''">
                            <img :src="image.url" alt="Existing product photo" class="aspect-square w-full rounded-lg object-cover">
                            <span class="mt-2 flex items-center gap-2 text-xs"><input type="checkbox" name="remove_images[]" :value="String(image.id)" x-model="removedImages"> Remove</span>
                        </label>
                    </template>
                </div>
            </template>
            <label class="block"><span class="field-label">Add photos</span><input x-ref="images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" @change="addImages($event)" class="field-control"></label>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <template x-for="(image, index) in selectedImages" :key="image.key">
                    <div class="rounded-xl border border-line p-2"><img :src="image.url" alt="New product photo preview" class="aspect-square w-full rounded-lg object-cover"><button type="button" class="btn-soft mt-2 w-full" @click="removeImage(index)">Remove</button></div>
                </template>
            </div>
            <p x-show="!existingImages.length && !selectedImages.length" class="text-sm text-charcoal/55">No photos selected. You can save a draft and add them later.</p>
            <p class="field-error" role="alert" x-show="imageError" x-text="imageError"></p>
            <p class="text-xs text-charcoal/55">If validation fails after a page reload, select new files again. Existing photos are retained.</p>
            @error('images')<p class="field-error">{{ $message }}</p>@enderror
        </x-seller.form-section>
        <x-seller.form-section title="Pricing and stock" description="For variant products, price and total stock are calculated from active variants.">
            <div x-show="!variantMode" class="grid gap-4 sm:grid-cols-2">
                <label><span class="field-label">Price (&#8369;)</span><input name="price" type="number" step="0.01" min="0" max="99999999.99" x-model="price" value="{{ old('price', $product->price) }}" :disabled="variantMode" class="field-control">@error('price')<p class="field-error">{{ $message }}</p>@enderror</label>
                <label><span class="field-label">Stock quantity</span><input name="stock_quantity" type="number" min="0" max="2147483647" x-model="stock" value="{{ old('stock_quantity', $product->stock_quantity) }}" :disabled="variantMode" class="field-control"></label>
            </div>
            <label class="block max-w-sm"><span class="field-label">Low-stock alert threshold *</span><input name="low_stock_threshold" type="number" min="0" max="1000000" required value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? config('shop.low_stock_threshold', 5)) }}" class="field-control"><span class="mt-1 block text-xs text-charcoal/55">Alert when total stock or any active variant reaches this level.</span></label>
        </x-seller.form-section>
        <x-seller.form-section title="Variants" description="Use options such as size or colour. Existing variants keep their IDs so order and cart references remain intact.">
            @if (! $editing)
                <div class="space-y-3">
                    <template x-for="(option, index) in options" :key="option.key">
                        <div class="grid gap-2 rounded-xl border border-line p-3 sm:grid-cols-[1fr_2fr_auto]">
                            <label><span class="field-label">Option name</span><input :name="'options[' + index + '][name]'" x-model="option.name" maxlength="100" placeholder="Size" class="field-control"></label>
                            <label><span class="field-label">Values (comma separated)</span><input :name="'options[' + index + '][values]'" x-model="option.values" maxlength="500" placeholder="Small, Medium, Large" class="field-control"></label>
                            <button type="button" class="btn-soft self-end" @click="removeOption(index)">Remove</button>
                        </div>
                    </template>
                </div>
                <div class="flex flex-wrap gap-2"><button type="button" class="btn-soft" :disabled="options.length >= 3" @click="addOption()">Add option</button><button x-show="options.length" type="button" class="btn-soft" @click="generateVariants()">Generate variant combinations</button></div>
            @elseif (! $product->hasVariants())
                <p class="text-sm text-charcoal/60">This product has no variants.</p>
            @endif
            <p x-show="!variants.length" class="text-sm text-charcoal/55">No variants. The product uses the price and stock above.</p>
            <p x-show="variantError" x-text="variantError" class="field-error" role="alert"></p>
            <div class="space-y-3">
                <template x-for="(variant, index) in variants" :key="variant.key">
                    <div class="space-y-3 rounded-xl border border-line p-4">
                        <h3 class="font-semibold" x-text="variantLabel(variant)"></h3>
                        <template x-if="variant.id"><input type="hidden" :name="'variants[' + index + '][id]'" :value="variant.id"></template>
                        <template x-if="!editing">
                            <div><template x-for="(value, key) in variant.options" :key="key"><input type="hidden" :name="'variants[' + index + '][options][' + key + ']'" :value="value"></template></div>
                        </template>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <label><span class="field-label">Price (&#8369;)</span><input type="number" min="0" max="99999999.99" step="0.01" :name="'variants[' + index + '][price]'" x-model="variant.price" class="field-control"></label>
                            <label><span class="field-label">Stock</span><input type="number" min="0" max="2147483647" :name="'variants[' + index + '][stock_quantity]'" x-model="variant.stock_quantity" class="field-control"></label>
                            <label><span class="field-label">SKU</span><input :name="'variants[' + index + '][sku]'" maxlength="100" x-model="variant.sku" class="field-control"></label>
                            <label><span class="field-label">Weight (kg)</span><input type="number" min="0" max="99999.999" step="0.001" :name="'variants[' + index + '][weight_kg]'" x-model="variant.weight_kg" placeholder="Use product weight" class="field-control"></label>
                            <label><span class="field-label">Availability</span><select :name="'variants[' + index + '][is_active]'" x-model.number="variant.is_active" class="field-control"><option value="1">Active</option><option value="0">Inactive</option></select></label>
                        </div>
                        <div class="flex flex-wrap items-start gap-3">
                            <img x-show="variant.preview || (variant.image_url && !variant.remove_image)" :src="variant.preview || variant.image_url || null" alt="Variant photo preview" class="size-20 rounded-lg object-cover">
                            <label class="min-w-0 flex-1"><span class="field-label">Variant photo (optional)</span><input type="file" accept="image/jpeg,image/png,image/webp" :name="'variants[' + index + '][image]'" @change="previewVariant($event, variant)" class="field-control"></label>
                            <template x-if="variant.image_url"><label class="text-xs"><input type="checkbox" :name="'variants[' + index + '][remove_image]'" value="1" x-model="variant.remove_image"> Remove existing photo</label></template>
                        </div>
                    </div>
                </template>
            </div>
        </x-seller.form-section>
        <x-seller.form-section title="Shipping details" description="Set the packed weight in kilograms. A variant weight overrides this default.">
            <label class="block max-w-sm"><span class="field-label">Default package weight (kg)</span><input name="weight_kg" type="number" step="0.001" min="0" max="99999.999" value="{{ old('weight_kg', $product->weight_kg) }}" class="field-control"></label>
        </x-seller.form-section>
        <div class="flex flex-wrap gap-3">
            <button type="submit" name="status" value="draft" class="btn-soft" :disabled="busy">Save as draft</button>
            @if ($editing && $product->status === 'archived')<button type="submit" name="status" value="archived" class="btn-soft" :disabled="busy">Save inactive</button>@endif
            <button type="submit" name="status" value="active" class="btn-primary" :disabled="busy">{{ in_array($product->status, ['rejected', 'pending_review'], true) || $product->requires_review ? 'Fix and resubmit' : ($product->status === 'active' ? 'Save changes' : 'Publish product') }}</button>
        </div>
    </div>
    <aside class="space-y-5 xl:sticky xl:top-24">
        <x-seller.form-section title="Buyer preview">
            <div class="overflow-hidden rounded-xl border border-line bg-white">
                <img x-show="cover" :src="cover || null" alt="Buyer listing preview" class="aspect-square w-full object-cover">
                <div x-show="!cover" class="flex aspect-square items-center justify-center bg-light-gray text-sm text-charcoal/50">Add a product photo</div>
                <div class="space-y-2 p-4"><h3 class="break-words font-semibold" x-text="name || 'Product name'"></h3><p class="text-lg font-bold text-primary" x-text="previewPrice"></p><p class="text-xs text-charcoal/55" x-text="category || 'Choose a category'"></p></div>
            </div>
            <p class="text-xs text-charcoal/50">Preview uses the regular price. Manage sale prices in Discounts.</p>
        </x-seller.form-section>
        <x-seller.form-section title="Before publishing">
            <ul class="space-y-2 text-sm">
                <li x-text="(name.trim() ? 'Ready: ' : 'Missing: ') + 'Product name'"></li>
                <li x-text="(category ? 'Ready: ' : 'Missing: ') + 'Category selected'"></li>
                <li x-text="(cover ? 'Ready: ' : 'Missing: ') + 'At least one photo'"></li>
                <li x-text="(pricingComplete ? 'Ready: ' : 'Missing: ') + 'Price and stock entered'"></li>
            </ul>
            <p class="text-xs text-charcoal/55">Drafts need a name and category. Add photos and pricing when you are ready to publish.</p>
        </x-seller.form-section>
    </aside>
</form>
