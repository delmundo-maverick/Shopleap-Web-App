<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/css/pages/auth-register.css', 'resources/js/app.js', 'resources/js/pages/auth/logistics-register.js'])

    <title>Logistics / Sorting Center Registration | Shopleap</title>
</head>

<body x-data="otpRegistration({
    verifyUrl: '{{ route('register.otp.verify', 'logistics') }}',
    resendUrl: '{{ route('register.resend-otp', 'logistics') }}',
    loginUrl: '{{ route('login') }}'
})" class="min-h-screen bg-ice-blue text-charcoal antialiased">

    @php
        $labelClass = 'mb-2 block text-sm font-semibold text-charcoal';
        $inputClass =
            'w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10';
        $selectClass = $inputClass . ' disabled:cursor-not-allowed disabled:bg-light-gray';
        $readonlyClass =
            'w-full cursor-not-allowed rounded-xl border border-light-gray bg-light-gray px-4 py-3.5 text-sm text-charcoal/70 outline-none';
        $iconWrapClass = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary';
    @endphp

    <!-- HEADER -->
    <header class="border-b border-light-gray bg-white">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-white/10">
                    <img src="{{ asset('images/Shopleap-Logo.png') }}" alt="Shopleap"
                        class="h-full w-full object-contain">
                </div>
                <div>
                    <h1 class="font-logo text-[1.375rem] text-primary font-medium leading-none tracking-tight">
                        Shopleap
                    </h1>
                    <p class="text-xs text-charcoal/60">
                        Shop Smart. Shop Simple.
                    </p>
                </div>
            </a>
            <div class="flex items-center gap-2 text-sm">
                <span class="hidden text-charcoal/60 sm:inline">Already have an account?</span>
                <a href="{{ route('login') }}" class="font-semibold text-primary transition hover:text-sky-blue">Log
                    in</a>
            </div>
        </div>
    </header>

    <main class="px-4 py-10 sm:px-6 lg:py-14">
        <div class="mx-auto max-w-4xl">

            <form method="POST" action="{{ route('register.logistics') }}" enctype="multipart/form-data"
                @submit.prevent="submitRegistration($event)"
                class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-xl shadow-charcoal/5">

                @csrf

                <div class="mb-8 text-center">
                    <h2 class="mt-5 text-3xl font-bold tracking-tight text-charcoal">Logistics / Sorting Center
                        Registration</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-charcoal/65">
                        Register your sorting center to manage parcel routing and rider assignments on Shopleap.
                    </p>
                </div>

                <!-- AJAX validation errors -->
                <div x-show="formErrors.length > 0" x-cloak
                    class="mx-6 mt-6 rounded-xl border border-sale-red/20 bg-sale-red/10 p-4 sm:mx-8">
                    <p class="text-sm font-semibold text-sale-red">Please correct the following errors:</p>
                    <ul class="mt-1 list-inside list-disc text-xs text-sale-red/80">
                        <template x-for="error in formErrors" :key="error">
                            <li x-text="error"></li>
                        </template>
                    </ul>
                </div>

                @if ($errors->any())
                    <div class="mx-6 mt-6 rounded-xl border border-sale-red/20 bg-sale-red/10 p-4 sm:mx-8">
                        <p class="text-sm font-semibold text-sale-red">Please correct the following errors:</p>
                        <ul class="mt-1.5 space-y-1 list-inside list-disc text-xs text-sale-red/80">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- SECTION 1: ACCOUNT INFORMATION -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Account Information</h3>
                            <p class="text-xs text-charcoal/55">Your login credentials for managing the sorting hub</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}">E-mail Address <span
                                    class="text-sale-red">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                placeholder="e.g. hub.admin@shopleap.ph" class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Password <span class="text-sale-red">*</span></label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required minlength="8"
                                    placeholder="At least 8 characters" class="{{ $inputClass }} pr-11">
                                <button type="button" id="togglePasswordBtn"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-charcoal/40 transition hover:text-primary">
                                    <svg id="password-icon" class="h-5 w-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Confirm Password <span
                                    class="text-sale-red">*</span></label>
                            <div class="relative">
                                <input type="password" id="password_confirmation" name="password_confirmation" required
                                    minlength="8" placeholder="Re-enter password" class="{{ $inputClass }} pr-11">
                                <button type="button" id="toggleConfirmPasswordBtn"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-charcoal/40 transition hover:text-primary">
                                    <svg id="confirm-password-icon" class="h-5 w-5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 2: PERSONAL INFORMATION -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Hub Manager Personal Details</h3>
                            <p class="text-xs text-charcoal/55">Primary contact person responsible for this location
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label class="{{ $labelClass }}">Last Name <span class="text-sale-red">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                placeholder="e.g. Dela Cruz" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">First Name <span
                                    class="text-sale-red">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                placeholder="e.g. Juan" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Middle Initial</label>
                            <input type="text" name="middle_initial" maxlength="1"
                                value="{{ old('middle_initial') }}" placeholder="M"
                                class="{{ $inputClass }} uppercase text-center">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Sex <span class="text-sale-red">*</span></label>
                            <select name="sex" required class="{{ $selectClass }}">
                                <option value="" disabled {{ old('sex') ? '' : 'selected' }}>Select sex</option>
                                <option value="male" {{ old('sex') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('sex') === 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Birthday <span class="text-sale-red">*</span></label>
                            <input type="date" id="birthday" name="birthday" value="{{ old('birthday') }}"
                                required class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Age</label>
                            <input type="text" id="age" readonly placeholder="Auto-calculated"
                                class="{{ $readonlyClass }}">
                        </div>
                        <div class="md:col-span-3">
                            <label class="{{ $labelClass }}">Contact No. <span
                                    class="text-sale-red">*</span></label>
                            <input type="tel" name="contact_no" value="{{ old('contact_no') }}" required
                                placeholder="09123456789" class="{{ $inputClass }}">
                        </div>
                    </div>
                </section>

                <!-- SECTION 3: ADDRESS -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Sorting Hub Location</h3>
                            <p class="text-xs text-charcoal/55">Physical address of your sorting/logistics center</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label class="{{ $labelClass }}">Province <span class="text-sale-red">*</span></label>
                            <select id="province" name="province" required class="{{ $selectClass }}">
                                <option value="">Loading provinces...</option>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Municipality/City <span
                                    class="text-sale-red">*</span></label>
                            <select id="municipality" name="municipality" required disabled
                                class="{{ $selectClass }}">
                                <option value="">Select province first</option>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Barangay <span class="text-sale-red">*</span></label>
                            <select id="barangay" name="barangay" required disabled class="{{ $selectClass }}">
                                <option value="">Select municipality first</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="{{ $labelClass }}">Street / House No. / Building, etc. <span
                                class="text-sale-red">*</span></label>
                        <input type="text" name="street_address" value="{{ old('street_address') }}" required
                            placeholder="e.g. Warehouse 4B, Industrial Zone Road" class="{{ $inputClass }}">
                    </div>
                </section>

                <!-- SECTION 4: BUSINESS INFORMATION -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0v-5a2 2 0 012-2h2a2 2 0 012 2v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Business Information</h3>
                            <p class="text-xs text-charcoal/55">Official registered entity for this logistics facility
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Business / Sorting Hub Name <span
                                class="text-sale-red">*</span></label>
                        <input type="text" name="business_name" value="{{ old('business_name') }}" required
                            placeholder="e.g. Laguna Central Sorting Hub" class="{{ $inputClass }}">
                    </div>
                </section>

                <!-- SECTION 5: DOCUMENT UPLOAD -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Document Upload</h3>
                            <p class="text-xs text-charcoal/55">Upload official documents to verify your facility</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">Upload Valid ID <span
                                    class="text-sale-red">*</span></label>
                            <div
                                class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-light-gray bg-ice-blue/30 p-6 text-center transition hover:border-primary/40 hover:bg-ice-blue/50">
                                <svg class="h-10 w-10 text-charcoal/30" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="mt-2 text-xs font-semibold text-charcoal">Click or drag file to upload</p>
                                <p class="mt-1 text-[11px] text-charcoal/45">JPG, PNG or PDF. Max 5MB.</p>
                                <input type="file" id="id_upload" name="id_upload" required
                                    accept=".jpg,.jpeg,.png,.pdf" class="absolute inset-0 cursor-pointer opacity-0">
                            </div>
                            <div id="id-file-badge"
                                class="mt-2.5 hidden items-center gap-2 rounded-lg border border-leaf-green/20 bg-leaf-green/10 px-3 py-1.5 text-xs font-medium text-leaf-green">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span id="id-file-name" class="truncate"></span>
                            </div>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Upload Business / DTI Permit <span
                                    class="text-sale-red">*</span></label>
                            <div
                                class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-light-gray bg-ice-blue/30 p-6 text-center transition hover:border-primary/40 hover:bg-ice-blue/50">
                                <svg class="h-10 w-10 text-charcoal/30" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2 text-xs font-semibold text-charcoal">Click or drag file to upload</p>
                                <p class="mt-1 text-[11px] text-charcoal/45">JPG, PNG or PDF. Max 5MB.</p>
                                <input type="file" id="business_permit" name="business_permit" required
                                    accept=".jpg,.jpeg,.png,.pdf" class="absolute inset-0 cursor-pointer opacity-0">
                            </div>
                            <div id="permit-file-badge"
                                class="mt-2.5 hidden items-center gap-2 rounded-lg border border-leaf-green/20 bg-leaf-green/10 px-3 py-1.5 text-xs font-medium text-leaf-green">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span id="permit-file-name" class="truncate"></span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 6: APPROVAL NOTICE & SUBMIT -->
                <section class="px-6 py-7 sm:px-8">
                    <div class="rounded-2xl border border-primary/10 bg-ice-blue p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-xs leading-5 text-charcoal/70">
                                <strong class="font-bold text-charcoal">Admin Approval Required:</strong>
                                After submitting your registration, please allow up to 24–48 hours for the administrator
                                to review your details. Activation status will be sent directly to your registered email
                                address.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-start gap-3">
                        <input type="checkbox" name="terms" id="terms" required
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-light-gray text-primary accent-primary focus:ring-2 focus:ring-primary/20">
                        <label for="terms" class="text-xs leading-5 text-charcoal/65">
                            I confirm that the information provided is accurate and complete, and I agree to the
                            <a href="#"
                                class="font-semibold text-primary underline hover:text-sky-blue">Shopleap Terms of
                                Service</a> and
                            <a href="#"
                                class="font-semibold text-primary underline hover:text-sky-blue">Logistics Partner
                                Policy</a>.
                            <span class="text-sale-red">*</span>
                        </label>
                    </div>

                    <button type="submit" :disabled="submitting"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-4 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!submitting">Submit Registration</span>
                        <span x-show="submitting" x-cloak>Submitting…</span>
                        <svg x-show="!submitting" class="h-5 w-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14m-6-6l6 6-6 6" />
                        </svg>
                    </button>

                    <div class="mt-5 text-center">
                        <a href="{{ route('register') }}"
                            class="text-sm font-semibold text-charcoal/60 transition hover:text-primary">
                            ← Back to account type selection
                        </a>
                    </div>
                </section>

            </form>

        </div>
    </main>

    <footer class="border-t border-light-gray bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-5 py-6 sm:flex-row">
            <p class="text-xs text-charcoal/60">© {{ date('Y') }} Shopleap. All rights reserved.</p>
            <p class="text-xs text-charcoal/60">Shop Smart. Shop Simple.</p>
        </div>
    </footer>

    <!-- OTP VERIFICATION MODAL -->
    <div x-show="showOtpModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-charcoal/50 px-4"
        @keydown.escape.window="closeOtpModal()">
        <div @click.outside="closeOtpModal()" x-transition
            class="w-full max-w-md rounded-2xl border border-light-gray bg-white p-8 shadow-2xl shadow-charcoal/20">

            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M3 8l7.9 5.26a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-charcoal">Verify your e-mail</h3>
                    <p class="mt-1 text-xs leading-5 text-charcoal/60">
                        Enter the 6-digit code sent to <span class="font-semibold text-charcoal"
                            x-text="email"></span>
                    </p>
                </div>
                <button type="button" @click="closeOtpModal()" class="ml-auto text-charcoal/40 hover:text-charcoal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div x-show="otpError" x-cloak x-text="otpError"
                class="mt-5 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-xs font-medium text-sale-red">
            </div>

            <div class="mt-6 flex justify-between gap-2" @paste="handleOtpPaste($event)">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                        x-ref="otpInput{{ $i }}" x-model="otpDigits[{{ $i }}]"
                        @input="handleOtpInput({{ $i }}, $event)"
                        @keydown="handleOtpKeydown({{ $i }}, $event)" :disabled="verifying"
                        class="h-14 w-full rounded-xl border border-light-gray bg-white text-center text-xl font-bold text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10 disabled:opacity-50">
                @endfor
            </div>

            <button type="button" @click="verifyOtp()" :disabled="verifying"
                class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-4 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-60">
                <span x-show="!verifying">Verify code</span>
                <span x-show="verifying">Verifying…</span>
            </button>

            <div class="mt-5 flex items-center justify-between text-xs">
                <button type="button" @click="resendOtp()" :disabled="resendCooldown > 0 || resending"
                    class="font-semibold text-primary transition hover:text-sky-blue disabled:cursor-not-allowed disabled:text-charcoal/40">
                    <span x-show="!resending">Resend code</span>
                    <span x-show="resending">Sending…</span>
                </button>
                <span class="text-charcoal/50" x-show="resendCooldown > 0"
                    x-text="`Resend in ${resendCooldown}s`"></span>
            </div>
        </div>
    </div>

</body>

</html>
