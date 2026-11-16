<?php

namespace App\Http\Requests\Company;

use App\Models\LogisticsProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'logistics_company';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['hub' => ['nullable', 'uuid'], 'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'export' => ['nullable', 'in:csv'], 'table' => ['nullable', 'in:hubs,riders,transactions']];
    }

    public function selectedHub(): ?LogisticsProfile
    {
        if (! $this->filled('hub')) {
            return null;
        }
        $hub = LogisticsProfile::where('public_code', $this->validated('hub'))->firstOrFail();
        abort_unless((int) $hub->company_id === (int) $this->user()->company_id, 403);

        return $hub;
    }
}
