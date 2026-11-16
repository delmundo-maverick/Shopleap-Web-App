<x-company.layout title="Company account status" description="{{ $company->name }}">
    <section class="max-w-2xl space-y-4 rounded-sm border border-line bg-surface p-5">
        <x-status-label :status="auth()->user()->account_status !== 'active' ? auth()->user()->account_status : $company->status" domain="company" class="rounded-sm!" />
        <h2 class="text-lg font-semibold">{{ match ($company->status) { 'pending' => 'Your company is awaiting approval', 'rejected' => 'Your application was rejected', 'suspended' => 'Your company account is suspended', default => 'Your company account is unavailable' } }}</h2>
        <p>{{ match ($company->status) { 'pending' => 'We will email the account holder after an administrator reviews your application.', 'rejected' => 'Please contact support about your application.', 'suspended' => 'Please contact support to restore access.', default => 'Please contact support about your account status.' } }}</p>
        @if ($company->rejection_reason)<p><strong>Reason:</strong> {{ $company->rejection_reason }}</p>@endif
    </section>
</x-company.layout>
