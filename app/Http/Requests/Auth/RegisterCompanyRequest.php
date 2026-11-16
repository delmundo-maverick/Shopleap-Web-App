<?php

namespace App\Http\Requests\Auth;

use App\Models\Company;
use App\Rules\UniqueRoleEmail;
use App\Support\Portal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $rules = [
            'company_name' => ['required', 'string', 'max:150'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'sex' => ['required', 'in:male,female'],
            'birthday' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255', new UniqueRoleEmail('logistics_company')],
            'contact_no' => ['required', 'string', 'max:20'],
            'service_regions' => ['required', 'array', 'min:1'],
            'service_regions.*' => ['required', 'string', 'distinct', Rule::in(config('logistics.regions'))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['required', 'accepted'],
        ];

        foreach (Company::DOCUMENTS as $document) {
            $rules[$document['input']] = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = [];
        foreach (Company::DOCUMENTS as $document) {
            $attributes[$document['input']] = $document['label'];
        }

        return $attributes;
    }
}
