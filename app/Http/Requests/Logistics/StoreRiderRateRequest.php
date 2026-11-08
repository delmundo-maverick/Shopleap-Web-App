<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiderRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'logistics';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['amount' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99']];
    }
}
