<?php

namespace App\Http\Requests\Auth;

use App\Rules\UniqueRoleEmail;
use App\Support\Portal;
use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Portal::isLogistics($this);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'contact_email' => ['required', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', new UniqueRoleEmail('logistics_company')],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}
