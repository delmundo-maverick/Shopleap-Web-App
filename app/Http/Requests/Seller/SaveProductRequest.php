<?php

namespace App\Http\Requests\Seller;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product ? $this->user()->can('update', $product) : $this->user()->role === 'seller';
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $draft = $this->input('status') === Product::STATUS_DRAFT;
        $hasVariants = $product ? $product->hasVariants() : $this->filled('variants');

        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(collect(config('shopleap_categories.list'))->pluck('name'))],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in([Product::STATUS_DRAFT, Product::STATUS_ACTIVE, Product::STATUS_ARCHIVED])],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.999'],
            'price' => [! $draft && ! $hasVariants ? 'required' : 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'stock_quantity' => [! $draft && ! $hasVariants ? 'required' : 'nullable', 'integer', 'min:0', 'max:2147483647'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'distinct'],
            'options' => [! $product && $hasVariants ? 'required' : 'nullable', 'array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:100', 'distinct:ignore_case', 'not_regex:/[\\[\\]]/'],
            'options.*.values' => ['required', 'string', 'max:500'],
            'variants' => [$hasVariants ? 'required' : 'nullable', 'array', 'min:1', 'max:100'],
            'variants.*.id' => [$product && $hasVariants ? 'required' : 'prohibited', 'integer', 'distinct'],
            'variants.*.options' => [! $product && $hasVariants ? 'required' : 'nullable', 'array'],
            'variants.*.price' => [$draft ? 'nullable' : 'required', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.stock_quantity' => [$draft ? 'nullable' : 'required', 'integer', 'min:0', 'max:2147483647'],
            'variants.*.sku' => ['nullable', 'string', 'max:100', 'distinct'],
            'variants.*.weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.999'],
            'variants.*.is_active' => ['required', 'boolean'],
            'variants.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'variants.*.remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $category = collect(config('shopleap_categories.list'))->firstWhere('name', $this->input('category'));
            if ($this->filled('subcategory') && ! in_array($this->input('subcategory'), $category['subcategories'] ?? [], true)) {
                $validator->errors()->add('subcategory', 'Choose a subcategory belonging to this category.');
            }

            $product = $this->route('product');
            $totalStock = collect($this->input('variants', []))->filter(fn (array $row): bool => (bool) $row['is_active'])->sum('stock_quantity');
            if ($totalStock > 2147483647) {
                $validator->errors()->add('variants', 'The total active variant stock is too large.');
            }
            if (! $product && $this->filled('options') && ! $this->filled('variants')) {
                $validator->errors()->add('variants', 'Generate variant combinations before saving, or remove the option groups.');
            }
            $removed = $this->input('remove_images', []);
            if ($product && count($removed) !== $product->images()->whereKey($removed)->count()) {
                $validator->errors()->add('images', 'An image does not belong to this product.');
            }
            $remainingImages = $product ? $product->images()->whereNotIn('id', $removed)->count() : 0;
            if ($remainingImages + count($this->file('images', [])) > 5) {
                $validator->errors()->add('images', 'Keep at most five product images.');
            }

            if ($product && $product->hasVariants()) {
                $ids = collect($this->input('variants', []))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                if ($ids->all() !== $product->variants()->orderBy('id')->pluck('id')->all()) {
                    $validator->errors()->add('variants', 'Submit every existing variant. Variants referenced by orders cannot be replaced.');
                }
            } elseif ($product && $this->filled('variants')) {
                $validator->errors()->add('variants', 'Create a new product to use a different variant structure.');
            }

            if ($this->input('status') === Product::STATUS_DRAFT) {
                return;
            }

            $rows = collect($this->input('variants', []));
            if ($rows->isNotEmpty() && ! $rows->contains(fn ($row) => (bool) $row['is_active'])) {
                $validator->errors()->add('variants', 'Keep at least one variant active before publishing.');
            }
            $hasVariantPhoto = $rows->contains(function (array $row, int $index) use ($product): bool {
                return $this->hasFile("variants.{$index}.image")
                    || ($product && empty($row['remove_image']) && $product->variants->firstWhere('id', $row['id'] ?? null)?->image_path);
            });
            if (! $remainingImages && ! $this->hasFile('images') && ! $hasVariantPhoto) {
                $validator->errors()->add('images', 'Add a product or variant photo before publishing.');
            }
        }];
    }
}
