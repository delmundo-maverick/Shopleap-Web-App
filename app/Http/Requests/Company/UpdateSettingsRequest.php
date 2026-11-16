<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'logistics_company';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'address' => ['required', 'string', 'max:2000'],
            'contact_phone' => ['required', 'string', 'max:20'], 'contact_email' => ['required', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'default_rider_rate' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99']];
    }
}
