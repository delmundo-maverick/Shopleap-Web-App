<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'logistics_company';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['province' => ['nullable', 'string', 'max:255'], 'municipality' => ['nullable', 'string', 'max:255'], 'needs_rider' => ['nullable', 'boolean']];
    }
}
