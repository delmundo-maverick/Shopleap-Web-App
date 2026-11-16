<?php

namespace App\Http\Requests\Company;

use App\Models\User;
use App\Support\PhilippinesCoordinates;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveHubRequest extends FormRequest
{
    public function authorize(): bool
    {
        $hub = $this->route('hub');

        return $this->user()?->role === 'logistics_company'
            && (! $hub || (int) $hub->company_id === (int) $this->user()->company_id);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['manager_email' => strtolower(trim((string) $this->input('manager_email')))]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'barangays' => ['required', 'array', 'min:1'],
            'barangays.*' => ['required', 'string', 'max:255', 'distinct'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90', 'decimal:0,7'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180', 'decimal:0,7'],
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_phone' => ['required', 'string', 'max:20'],
            'manager_email' => ['required', 'email', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                if (User::withEmail((string) $value)->where('role', 'logistics')
                    ->when($this->route('hub'), fn ($query) => $query->whereKeyNot($this->route('hub')->user_id))->exists()) {
                    $fail('This email is already registered as a hub manager.');
                }
            }],
        ];
    }

    /** @return list<Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['latitude', 'longitude']) && $this->filled('latitude') && $this->filled('longitude')
                && ! PhilippinesCoordinates::contains((float) $this->input('latitude'), (float) $this->input('longitude'))) {
                $validator->errors()->add('latitude', 'Choose a location inside the Philippines.');
            }
        }];
    }
}
