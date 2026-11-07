<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'logistics';
    }

    public function rules(): array
    {
        return [
            'range' => ['nullable', Rule::in(['today', '7d', '30d'])],
            'queue' => ['nullable', Rule::in(['all', 'sorting', 'inbound', 'outbound', 'delivery', 'delivered', 'applications', 'stale', 'failed', 'returned'])],
        ];
    }
}
