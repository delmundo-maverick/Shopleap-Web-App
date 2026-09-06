<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Seller Registration | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    @php
        // Shared Tailwind class strings so every field stays visually consistent
        // and a style tweak only has to happen in one place.
        $labelClass = 'mb-2 block text-sm font-semibold text-charcoal';
        $inputClass =
            'w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10';
        $selectClass = $inputClass . ' disabled:cursor-not-allowed disabled:bg-light-gray';
        $readonlyClass =
            'w-full cursor-not-allowed rounded-xl border border-light-gray bg-light-gray px-4 py-3.5 text-sm text-charcoal/70 outline-none';
        $iconWrapClass = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary';

        $businessCategories = [
            'Fashion & Apparel',
            'Electronics',
            'Food & Beverage',
            'Health & Beauty',
            'Home & Living',
            'Automotive',
            'Sports & Outdoors',
            'Books & Hobbies',
            'Others',
        ];
    @endphp

    <!-- ===================== HEADER ===================== -->
    <header class="border-b border-light-gray bg-white">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ice-blue">
                    {{-- Add your logo later:
                    <img src="{{ asset('images/logo.png') }}" alt="Shopleap Logo" class="h-full w-full rounded-xl object-contain"> --}}
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-tight text-primary">Shopleap</h1>
                    <p class="text-xs text-charcoal/60">Shop Smart. Shop Simple.</p>
                </div>
            </a>

            <div class="flex items-center gap-2 text-sm">
                <span class="hidden text-charcoal/60 sm:inline">Already have an account?</span>
                <a href="{{ route('login') }}" class="font-semibold text-primary transition hover:text-sky-blue">Log
                    in</a>
            </div>
        </div>
    </header>

    <!-- ===================== MAIN ===================== -->
    <main class="px-4 py-10 sm:px-6 lg:py-14">
        <div class="mx-auto max-w-4xl">

            <form method="POST" action="{{ route('register.seller') }}" enctype="multipart/form-data"
                class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-xl shadow-charcoal/5">
                @csrf

                @if ($errors->any())
                    <div class="mx-6 mt-6 rounded-xl border border-sale-red/20 bg-sale-red/10 p-4 sm:mx-8">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-sale-red" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v3m0 4h.01M10.3 3.8L2.9 17a2 2 0 001.75 3h14.7a2 2 0 001.75-3L13.7 3.8a2 2 0 00-3.4 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-sale-red">Please correct the following errors:</p>
                                <ul class="mt-1 list-inside list-disc text-xs text-sale-red/80">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- ===================== ACCOUNT INFORMATION ===================== -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-start gap-4">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M15 7a3 3 0 11-6 0 3 3 0 016 0zM5 21a7 7 0 0114 0M17 11h4m-2-2v4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Account Information</h3>
                            <p class="mt-1 text-xs text-charcoal/55">These details will be used to access your Shopleap
                                account.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="email" class="{{ $labelClass }}">E-mail <span
                                    class="text-sale-red">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                autocomplete="email" placeholder="Enter your e-mail address"
                                class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label for="password" class="{{ $labelClass }}">Password <span
                                    class="text-sale-red">*</span></label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required minlength="8"
                                    autocomplete="new-password" placeholder="Create a password"
                                    class="{{ $inputClass }} pr-11">
                                <button type="button" onclick="togglePassword('password', 'passwordEye')"
                                    class="absolute inset-y-0 right-0 flex items-center px-4 text-charcoal/40 hover:text-primary">
                                    <svg id="passwordEye" class="h-5 w-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />
                                        <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="{{ $labelClass }}">Confirm Password <span
                                    class="text-sale-red">*</span></label>
                            <div class="relative">
                                <input type="password" id="password_confirmation" name="password_confirmation" required
                                    minlength="8" autocomplete="new-password" placeholder="Confirm your password"
                                    class="{{ $inputClass }} pr-11">
                                <button type="button"
                                    onclick="togglePassword('password_confirmation', 'confirmPasswordEye')"
                                    class="absolute inset-y-0 right-0 flex items-center px-4 text-charcoal/40 hover:text-primary">
                                    <svg id="confirmPasswordEye" class="h-5 w-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />
                                        <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ===================== PERSONAL INFORMATION ===================== -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-start gap-4">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5 21a7 7 0 0114 0" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Personal Information</h3>
                            <p class="mt-1 text-xs text-charcoal/55">Enter your personal details exactly as they appear
                                on your identification document.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">

                        <div>
                            <label for="first_name" class="{{ $labelClass }}">First Name <span
                                    class="text-sale-red">*</span></label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                                required autocomplete="given-name" placeholder="First name"
                                class="{{ $inputClass }}">
                        </div>
                        
                        <div>
                            <label for="last_name" class="{{ $labelClass }}">Last Name <span
                                    class="text-sale-red">*</span></label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                                required autocomplete="family-name" placeholder="Last name"
                                class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label for="middle_initial" class="{{ $labelClass }}">Middle Initial <span
                                    class="text-charcoal/40">(Optional)</span></label>
                            <input type="text" id="middle_initial" name="middle_initial"
                                value="{{ old('middle_initial') }}" maxlength="5" placeholder="M"
                                class="{{ $inputClass }} uppercase">
                        </div>

                        <div>
                            <label for="sex" class="{{ $labelClass }}">Sex <span
                                    class="text-sale-red">*</span></label>
                            <select id="sex" name="sex" required class="{{ $selectClass }}">
                                <option value="" disabled {{ old('sex') ? '' : 'selected' }}>Select sex</option>
                                <option value="male" {{ old('sex') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('sex') === 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>

                        <div>
                            <label for="birthday" class="{{ $labelClass }}">Birthday <span
                                    class="text-sale-red">*</span></label>
                            <input type="date" id="birthday" name="birthday" value="{{ old('birthday') }}"
                                required max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                class="{{ $inputClass }}">
                            <p class="mt-1.5 text-xs text-charcoal/45">Sellers must be at least 18 years old.</p>
                        </div>

                        <div>
                            <label for="age" class="{{ $labelClass }}">Age <span
                                    class="text-sale-red">*</span></label>
                            <input type="text" id="age" name="age" readonly
                                placeholder="Auto-generated" class="{{ $readonlyClass }}">
                            <p class="mt-1.5 text-xs text-charcoal/45">Automatically calculated from your birthday.</p>
                        </div>

                        <div class="md:col-span-3">
                            <label for="contact_no" class="{{ $labelClass }}">Contact No. <span
                                    class="text-sale-red">*</span></label>
                            <input type="tel" id="contact_no" name="contact_no" value="{{ old('contact_no') }}"
                                required autocomplete="tel" placeholder="e.g. 09171234567"
                                class="{{ $inputClass }}">
                        </div>
                    </div>
                </section>

                <!-- ===================== ADDRESS ===================== -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-start gap-4">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M12 21s7-5.2 7-11a7 7 0 10-14 0c0 5.8 7 11 7 11z" />
                                <circle cx="12" cy="10" r="2.5" stroke-width="1.8" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Address</h3>
                            <p class="mt-1 text-xs text-charcoal/55">Select your location and enter your complete
                                address.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label for="province" class="{{ $labelClass }}">Province <span
                                    class="text-sale-red">*</span></label>
                            <select id="province" name="province" required disabled class="{{ $selectClass }}">
                                <option value="" disabled selected>Loading provinces…</option>
                            </select>
                            <p id="provinceHelp" class="mt-1.5 text-xs text-charcoal/45">Fetching province list…</p>
                        </div>

                        <div>
                            <label for="municipality" class="{{ $labelClass }}">Municipality/City <span
                                    class="text-sale-red">*</span></label>
                            <select id="municipality" name="municipality" required disabled
                                class="{{ $selectClass }}">
                                <option value="">Select municipality</option>
                            </select>
                            <p id="municipalityHelp" class="mt-1.5 text-xs text-charcoal/45">Select a province first
                            </p>
                        </div>

                        <div>
                            <label for="barangay" class="{{ $labelClass }}">Barangay <span
                                    class="text-sale-red">*</span></label>
                            <select id="barangay" name="barangay" required disabled class="{{ $selectClass }}">
                                <option value="">Select barangay</option>
                            </select>
                            <p id="barangayHelp" class="mt-1.5 text-xs text-charcoal/45">Select a municipality first
                            </p>
                        </div>

                        <div class="md:col-span-3">
                            <label for="street_address" class="{{ $labelClass }}">Street / House No. / Building
                                <span class="text-sale-red">*</span></label>
                            <input type="text" id="street_address" name="street_address"
                                value="{{ old('street_address') }}" required
                                placeholder="e.g. 123 Rizal St., Purok 4" class="{{ $inputClass }}">
                        </div>
                    </div>
                </section>

                <!-- ===================== BUSINESS INFORMATION ===================== -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-start gap-4">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M3 3h18M4 3l1.5 9.5A2 2 0 007.48 14.5h9.04a2 2 0 001.98-2L20 3M9 19a1 1 0 11-2 0 1 1 0 012 0zm10 0a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Business Information</h3>
                            <p class="mt-1 text-xs text-charcoal/55">Tell us about the business you'll be listing
                                products under.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="business_name" class="{{ $labelClass }}">Business Name <span
                                    class="text-sale-red">*</span></label>
                            <input type="text" id="business_name" name="business_name"
                                value="{{ old('business_name') }}" required placeholder="Business name"
                                class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label for="line_of_business" class="{{ $labelClass }}">Line of Business <span
                                    class="text-sale-red">*</span></label>
                            <select id="line_of_business" name="line_of_business" required
                                class="{{ $selectClass }}">
                                <option value="" disabled {{ old('line_of_business') ? '' : 'selected' }}>Select
                                    category</option>
                                @foreach ($businessCategories as $category)
                                    <option value="{{ $category }}"
                                        {{ old('line_of_business') === $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <!-- ===================== DOCUMENT UPLOAD ===================== -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <div class="mb-6 flex items-start gap-4">
                        <div class="{{ $iconWrapClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M15 7a3 3 0 11-6 0 3 3 0 016 0zM4 21a8 8 0 0116 0M19 4l1 1 2-2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-charcoal">Document Upload</h3>
                            <p class="mt-1 text-xs text-charcoal/55">Upload the documents below for administrator
                                verification.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="id_upload"
                                class="group flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-light-gray bg-light-gray/40 px-6 py-10 text-center transition hover:border-primary/40 hover:bg-ice-blue/50">
                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-primary shadow-sm transition group-hover:bg-primary group-hover:text-white">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M12 16V4m0 0L8 8m4-4l4 4M5 20h14" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm font-bold text-charcoal">Upload your valid ID</p>
                                <p class="mt-1 text-xs leading-5 text-charcoal/55">
                                    Make sure the image or document is clear and readable.
                                </p>
                                <p class="mt-3 text-xs font-medium text-primary">JPG, JPEG, PNG or PDF</p>
                                <input type="file" id="id_upload" name="id_upload" required
                                    accept=".jpg,.jpeg,.png,.pdf" class="sr-only">
                            </label>

                            <div id="idFileSelected"
                                class="mt-3 hidden rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 shrink-0 text-leaf-green" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-leaf-green">Selected file</p>
                                        <p id="idFileName" class="truncate text-xs text-charcoal/65"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="business_permit"
                                class="group flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-light-gray bg-light-gray/40 px-6 py-10 text-center transition hover:border-primary/40 hover:bg-ice-blue/50">
                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-primary shadow-sm transition group-hover:bg-primary group-hover:text-white">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M12 16V4m0 0L8 8m4-4l4 4M5 20h14" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm font-bold text-charcoal">Upload business permit</p>
                                <p class="mt-1 text-xs leading-5 text-charcoal/55">
                                    Make sure the image or document is clear and readable.
                                </p>
                                <p class="mt-3 text-xs font-medium text-primary">JPG, JPEG, PNG or PDF</p>
                                <input type="file" id="business_permit" name="business_permit" required
                                    accept=".jpg,.jpeg,.png,.pdf" class="sr-only">
                            </label>

                            <div id="permitFileSelected"
                                class="mt-3 hidden rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 shrink-0 text-leaf-green" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-leaf-green">Selected file</p>
                                        <p id="permitFileName" class="truncate text-xs text-charcoal/65"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-4 text-xs leading-5 text-charcoal/50">
                        Your uploaded documents will be reviewed by the administrator and will only be used for
                        account verification.
                    </p>
                </section>

                <!-- ===================== ADMIN APPROVAL NOTICE ===================== -->
                <section class="px-6 py-7 sm:px-8">
                    <div class="rounded-2xl border border-primary/10 bg-ice-blue p-5">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-primary shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 9v3m0 4h.01M10.3 3.8L2.9 17a2 2 0 001.75 3h14.7a2 2 0 001.75-3L13.7 3.8a2 2 0 00-3.4 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-charcoal">Administrator approval required</h3>
                                <p class="mt-1 text-xs leading-5 text-charcoal/70 sm:text-sm">
                                    After submitting your registration, your application will be reviewed by a Shopleap
                                    administrator. You will receive an email notification once your registration has
                                    been approved or rejected.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-start gap-3">
                        <input type="checkbox" id="terms" name="terms" required
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-light-gray accent-primary focus:ring-primary/20">
                        <label for="terms" class="text-xs leading-5 text-charcoal/65">
                            I confirm that the information I provided is accurate and complete, and I agree to the
                            Shopleap terms and policies. <span class="text-sale-red">*</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-4 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/20">
                        Submit Registration
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <div class="mt-8 text-center">
                <p class="text-sm text-charcoal/65">
                    Already have a Shopleap account?
                    <a href="{{ route('login') }}"
                        class="font-bold text-primary hover:text-sky-blue hover:underline">Log in</a>
                </p>
            </div>
        </div>
    </main>

    <!-- ===================== FOOTER ===================== -->
    <footer class="border-t border-light-gray bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-5 py-6 sm:flex-row">
            <p class="text-xs text-charcoal/60">© {{ date('Y') }} Shopleap. All rights reserved.</p>
            <p class="text-xs text-charcoal/60">Shop Smart. Shop Simple.</p>
        </div>
    </footer>

    <!-- ===================== JAVASCRIPT ===================== -->
    <script>
        /* ---------------------------------------------------------------
                     | Password Visibility
                     | Icon markup is stored once per state and reused for both the
                     | password and confirm-password fields instead of being
                     | duplicated inline for each toggle.
                     * ------------------------------------------------------------- */
        const EYE_OPEN_ICON = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />
            <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
        `;

        const EYE_CLOSED_ICON = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.7 10.7 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3 3.8M6.2 6.2C3.7 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.6-.3 3.7-.8" />
        `;

        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            const isCurrentlyHidden = input.type === 'password';

            input.type = isCurrentlyHidden ? 'text' : 'password';
            icon.innerHTML = isCurrentlyHidden ? EYE_CLOSED_ICON : EYE_OPEN_ICON;
        }

        /* ---------------------------------------------------------------
         | Automatic Age Calculation
         * ------------------------------------------------------------- */
        const birthdayInput = document.getElementById('birthday');
        const ageInput = document.getElementById('age');

        function calculateAge() {
            if (!birthdayInput.value) {
                ageInput.value = '';
                return;
            }

            const birthday = new Date(birthdayInput.value);
            const today = new Date();

            let age = today.getFullYear() - birthday.getFullYear();

            const hasHadBirthdayThisYear =
                today.getMonth() > birthday.getMonth() ||
                (today.getMonth() === birthday.getMonth() && today.getDate() >= birthday.getDate());

            if (!hasHadBirthdayThisYear) age--;

            ageInput.value = age >= 0 ? age : '';
        }

        birthdayInput.addEventListener('change', calculateAge);

        /* ---------------------------------------------------------------
         | Document File Selection (ID + Business Permit)
         * ------------------------------------------------------------- */
        function wireFileInput(inputId, wrapperId, nameId) {
            const input = document.getElementById(inputId);
            const wrapper = document.getElementById(wrapperId);
            const nameEl = document.getElementById(nameId);

            input.addEventListener('change', function() {
                const file = this.files?.[0];
                nameEl.textContent = file?.name ?? '';
                wrapper.classList.toggle('hidden', !file);
            });
        }

        wireFileInput('id_upload', 'idFileSelected', 'idFileName');
        wireFileInput('business_permit', 'permitFileSelected', 'permitFileName');

        /* ---------------------------------------------------------------
         | Middle Initial (letters only)
         * ------------------------------------------------------------- */
        document.getElementById('middle_initial').addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase();
        });

        /* ---------------------------------------------------------------
         | Province / Municipality / Barangay cascading dropdowns
         |
         | Data source: the public PSGC (Philippine Standard Geographic
         | Code) API at https://psgc.gitlab.io/api/. Trailing slashes are
         | required on every endpoint below — without them the API issues
         | a redirect that breaks the CORS request from the browser.
         |
         | The three levels share identical load/reset/restore behaviour,
         | so they're driven by one generic cascade instead of three
         | near-duplicate functions.
         * ------------------------------------------------------------- */
        (function() {
            const PSGC_BASE = 'https://psgc.gitlab.io/api';

            // Old input, so a validation error doesn't wipe the user's selections.
            const OLD_VALUES = {
                province: @json(old('province')),
                municipality: @json(old('municipality')),
                barangay: @json(old('barangay')),
            };

            const levels = [{
                    select: document.getElementById('province'),
                    help: document.getElementById('provinceHelp'),
                    placeholder: 'Select province',
                    loadingText: 'Fetching province list…',
                    errorText: 'Could not load the province list. Please refresh the page.',
                    resetText: 'Select province',
                    url: () => `${PSGC_BASE}/provinces/`,
                    old: OLD_VALUES.province,
                },
                {
                    select: document.getElementById('municipality'),
                    help: document.getElementById('municipalityHelp'),
                    placeholder: 'Select municipality',
                    loadingText: 'Fetching municipality list…',
                    errorText: 'Could not load the municipality list. Please try again.',
                    resetText: 'Select a province first',
                    url: (code) => `${PSGC_BASE}/provinces/${code}/cities-municipalities/`,
                    old: OLD_VALUES.municipality,
                },
                {
                    select: document.getElementById('barangay'),
                    help: document.getElementById('barangayHelp'),
                    placeholder: 'Select barangay',
                    loadingText: 'Fetching barangay list…',
                    errorText: 'Could not load the barangay list. Please try again.',
                    resetText: 'Select a municipality first',
                    url: (code) => `${PSGC_BASE}/cities-municipalities/${code}/barangays/`,
                    old: OLD_VALUES.barangay,
                },
            ];

            function fillOptions(select, items, placeholder) {
                select.innerHTML = '';

                const placeholderOption = new Option(placeholder, '', true, true);
                placeholderOption.disabled = true;
                select.append(placeholderOption);

                items
                    .slice()
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .forEach((item) => {
                        const option = new Option(item.name, item.name);
                        option.dataset.code = item.code;
                        select.append(option);
                    });
            }

            function resetLevel(level, text) {
                level.select.innerHTML = '';
                level.select.append(new Option(text, ''));
                level.select.disabled = true;

                if (level.help) level.help.textContent = text;
            }

            function resetFrom(index) {
                for (let i = index; i < levels.length; i++) {
                    resetLevel(levels[i], levels[i].resetText);
                }
            }

            async function loadLevel(index, parentCode) {
                const level = levels[index];

                resetLevel(level, level.loadingText);
                resetFrom(index + 1);

                try {
                    const response = await fetch(level.url(parentCode));

                    if (!response.ok) {
                        throw new Error(`PSGC request failed: ${response.status} ${level.url(parentCode)}`);
                    }

                    const items = await response.json();

                    fillOptions(level.select, items, level.placeholder);
                    level.select.disabled = false;

                    if (level.help) {
                        level.help.textContent = `Select your ${level.placeholder.replace('Select ', '')}`;
                    }

                    // Restore the previous selection (validation round-trip)
                    // and cascade into the next level if it matches.
                    if (level.old) {
                        const match = [...level.select.options].find((option) => option.value === level.old);

                        if (match) {
                            level.select.value = level.old;

                            if (index + 1 < levels.length) {
                                await loadLevel(index + 1, match.dataset.code);
                            }
                        }
                    }
                } catch (error) {
                    console.error(error);
                    resetLevel(level, level.errorText);
                }
            }

            levels.forEach((level, index) => {
                const isLastLevel = index === levels.length - 1;
                if (isLastLevel) return;

                level.select.addEventListener('change', function() {
                    const code = this.options[this.selectedIndex]?.dataset.code;
                    if (code) loadLevel(index + 1, code);
                });
            });

            loadLevel(0);
        })();
    </script>
</body>

</html>
