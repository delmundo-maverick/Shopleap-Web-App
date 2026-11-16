<?php

namespace App\Http\Requests\Company;

use App\Models\ShipmentBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'logistics_company';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(array_keys(ShipmentBag::STATUS_LABELS))],
            'origin' => ['nullable', 'uuid'], 'destination' => ['nullable', 'uuid'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('date_from'), 'after_or_equal:date_from')],
            'late' => ['nullable', 'boolean'], 'missing' => ['nullable', 'boolean'],
        ];
    }
}
