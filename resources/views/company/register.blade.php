<x-logistics.public-layout title="Register as a Company" noindex :scripts="['resources/js/pages/company/register.js', 'resources/js/app.js']">
    <section class="portal-container portal-section">
        <div class="mx-auto max-w-3xl rounded-sm border border-line bg-surface p-5 text-charcoal sm:p-8" x-data="companyRegistration(@js(['termsAccepted' => (bool) old('terms_accepted')]))">
            <h1 class="text-2xl font-semibold">Register as a Company</h1>
            <p class="mt-2">Verify your email, then wait for company approval before adding hubs.</p>
            @if ($errors->any())<ul role="alert" class="my-4 text-danger">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
            <form method="POST" action="{{ route('company.register') }}" enctype="multipart/form-data" @submit="guardTermsAgreement($event)" class="mt-6 space-y-5">
                @csrf
                <fieldset class="grid gap-4 sm:grid-cols-2"><legend class="mb-4 text-lg font-semibold">Company details</legend>
                    @foreach (['company_name' => ['Company name', 'text', 255], 'address' => ['Address', 'text', 2000], 'contact_phone' => ['Contact phone', 'tel', 20], 'contact_email' => ['Contact email', 'email', 255]] as $name => [$label, $type, $max])
                        <label class="block text-sm font-medium">{{ $label }}<input name="{{ $name }}" type="{{ $type }}" maxlength="{{ $max }}" value="{{ old($name) }}" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                    @endforeach
                    <label class="block text-sm font-medium">Company logo (optional)<input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-sm border border-line p-3"><span class="text-xs">JPG, PNG or WebP, up to 2 MB.</span></label>
                </fieldset>
                <fieldset class="grid gap-4 sm:grid-cols-2"><legend class="mb-4 text-lg font-semibold">Account holder</legend>
                    @foreach (['name' => ['Full name', 'text', 255], 'email' => ['Email', 'email', 255], 'phone' => ['Phone', 'tel', 20], 'password' => ['Password', 'password', 255], 'password_confirmation' => ['Confirm password', 'password', 255]] as $name => [$label, $type, $max])
                        <label class="block text-sm font-medium">{{ $label }}<input name="{{ $name }}" type="{{ $type }}" maxlength="{{ $max }}" value="{{ $type === 'password' ? '' : old($name) }}" @if ($type === 'password') minlength="8" autocomplete="new-password" @endif required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                    @endforeach
                </fieldset>
                <x-terms-consent />
                <button type="submit" class="btn btn-primary">Verify email and submit</button>
            </form>
        </div>
    </section>
</x-logistics.public-layout>
