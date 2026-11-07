<?php

namespace App\Http\Requests\Seller;

use App\Models\ProductDiscount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'seller';
    }

    public function rules(): array
    {
        return [
            'product_ids' => [$this->route('discount') ? 'nullable' : 'required', 'array', 'min:1', 'max:100'],
            'product_ids.*' => ['required', 'integer', 'distinct'],
            'type' => ['required', Rule::in([ProductDiscount::TYPE_PERCENT, ProductDiscount::TYPE_FIXED])],
            'value' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => array_filter(['required', 'date', 'after:now', $this->filled('starts_at') ? 'after:starts_at' : null]),
        ];
    }
}
