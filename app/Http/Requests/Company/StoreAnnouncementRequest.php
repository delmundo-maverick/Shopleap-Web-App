<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'logistics_company';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:150'], 'body' => ['required', 'string', 'max:10000']];
    }
}
