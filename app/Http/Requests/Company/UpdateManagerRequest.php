<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $hub = $this->route('hub');

        return $this->user()?->role === 'logistics_company' && $hub?->company_id !== null
            && (int) $hub->company_id === (int) $this->user()->company_id;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:20']];
    }
}
