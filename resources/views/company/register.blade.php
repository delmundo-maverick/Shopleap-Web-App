<x-logistics.public-layout title="Create your company account" noindex :scripts="['resources/js/pages/company/register.js', 'resources/js/app.js']">
    <section class="portal-container portal-section">
        <div class="mx-auto max-w-4xl rounded-sm border border-line bg-surface p-5 text-charcoal sm:p-8" x-data="companyRegistration(@js(['termsAccepted' => (bool) old('terms_accepted')]))" data-locations-url="{{ route('company.register.locations') }}" data-province="{{ old('province') }}" data-municipality="{{ old('municipality') }}" data-barangay="{{ old('barangay') }}" data-birthday="{{ old('birthday') }}">
            <h1 class="text-2xl font-semibold">Create your logistics company account</h1>
            <p class="mt-2">This is the mother account for your delivery network. Submit your company details and verification documents, verify your email, and wait for administrator approval. Then create your branches under Hubs in the company dashboard.</p>
            <p class="mt-3 text-sm">Joining an existing branch? Your company creates the branch and sends its manager an invitation to set a password.</p>
            @if ($errors->any())<ul role="alert" class="my-4 text-danger">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
            <form method="POST" action="{{ route('company.register') }}" enctype="multipart/form-data" @submit="guardTermsAgreement($event)" class="mt-6 space-y-5">
                @csrf
                <fieldset class="grid gap-4 sm:grid-cols-2"><legend class="mb-4 text-lg font-semibold">Company details</legend>
                    <label class="block text-sm font-medium">Company name<input name="company_name" type="text" maxlength="150" value="{{ old('company_name') }}" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                    <label class="block text-sm font-medium">Company logo (optional)<input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-sm border border-line p-3"><span class="text-xs">JPG, PNG or WebP, up to 2 MB.</span></label>
                </fieldset>
                <fieldset class="grid gap-4 sm:grid-cols-2"><legend class="mb-4 text-lg font-semibold">Account information</legend>
                    @foreach (['email' => ['Email', 'email', 255], 'password' => ['Password', 'password', 255], 'password_confirmation' => ['Confirm password', 'password', 255]] as $name => [$label, $type, $max])
                        <label class="block text-sm font-medium">{{ $label }}<input name="{{ $name }}" type="{{ $type }}" maxlength="{{ $max }}" value="{{ $type === 'password' ? '' : old($name) }}" @if ($type === 'password') minlength="8" autocomplete="new-password" @endif required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                    @endforeach
                </fieldset>
                <fieldset class="grid gap-4 sm:grid-cols-3"><legend class="mb-4 text-lg font-semibold">Authorized representative's personal details</legend>
                    @foreach (['last_name' => 'Last name', 'first_name' => 'First name'] as $name => $label)
                        <label class="block text-sm font-medium">{{ $label }}<input type="text" name="{{ $name }}" value="{{ old($name) }}" maxlength="100" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                    @endforeach
                    <label class="block text-sm font-medium">Middle initial (optional)<input type="text" name="middle_initial" value="{{ old('middle_initial') }}" maxlength="1" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                    <label class="block text-sm font-medium">Sex<select name="sex" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">Select sex</option><option value="male" @selected(old('sex') === 'male')>Male</option><option value="female" @selected(old('sex') === 'female')>Female</option></select></label>
                    <label class="block text-sm font-medium">Birthday<input type="date" name="birthday" x-model="birthday" value="{{ old('birthday') }}" max="{{ now('Asia/Manila')->subDay()->toDateString() }}" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                    <label class="block text-sm font-medium">Age<input type="number" :value="age" readonly class="mt-1 block w-full rounded-sm border border-line bg-ice-blue p-3"><span class="text-xs">Calculated from your birthday.</span></label>
                    <label class="block text-sm font-medium sm:col-span-3">Contact number<input type="tel" name="contact_no" value="{{ old('contact_no') }}" maxlength="20" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><span class="text-xs">Your account email and contact number are also used as the company's contact details.</span></label>
                </fieldset>
                <fieldset class="space-y-4"><legend class="mb-2 text-lg font-semibold">Company address</legend>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="block text-sm font-medium">Province<select name="province" x-ref="province" required disabled class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">Loading provinces...</option></select></label>
                        <label class="block text-sm font-medium">Municipality / City<select name="municipality" x-ref="municipality" required disabled class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">Select a province first</option></select></label>
                        <label class="block text-sm font-medium">Barangay<select name="barangay" x-ref="barangay" required disabled class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">Select a municipality first</option></select></label>
                    </div>
                    <p x-ref="locationHelp" role="status" aria-live="polite" class="text-sm text-danger"></p>
                    <button type="button" @click="retryLocations()" class="text-sm text-primary">Reload location lists</button>
                    <label class="block text-sm font-medium">House / Building number and street<input type="text" name="street_address" value="{{ old('street_address') }}" maxlength="255" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                </fieldset>
                <fieldset class="space-y-3"><legend class="mb-2 text-lg font-semibold">Service regions</legend>
                    <p class="text-sm">Select at least one region your company plans to serve. Detailed branch coverage is configured when you create hubs after approval.</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach (config('logistics.regions') as $region)
                            <label class="flex items-center gap-2 rounded-sm border border-line bg-surface p-3 text-sm"><input type="checkbox" name="service_regions[]" value="{{ $region }}" @checked(in_array($region, old('service_regions', []), true))><span>{{ $region }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset class="space-y-4">
                    <legend class="mb-2 text-lg font-semibold">Required verification documents</legend>
                    <p class="text-sm">Upload all four documents for the company and its authorized representative. Each file must be JPG, PNG or PDF, up to 5 MB. Documents are stored privately for administrator review.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach (\App\Models\Company::DOCUMENTS as $document)
                            <label class="block text-sm font-medium">{{ $document['label'] }}
                                <input type="file" name="{{ $document['input'] }}" accept="image/jpeg,image/png,application/pdf" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3">
                                @error($document['input'])<span role="alert" class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs">Use the representative's valid ID and the company's business documents. If validation fails, select the files again before submitting.</p>
                </fieldset>
                <x-terms-consent />
                <button type="submit" class="btn btn-primary">Verify email and register company</button>
            </form>
        </div>
    </section>
</x-logistics.public-layout>
