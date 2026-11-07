<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RiderApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'logistics';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'pending', 'approved', 'rejected'])],
        ];
    }
}
