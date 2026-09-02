<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Seller Registration | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="border-b border-light-gray bg-white">

        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">

            <a href="{{ route('home') }}" class="flex items-center gap-3">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ice-blue"></div>

                <div>
                    <h1 class="text-lg font-bold leading-tight text-primary">Shopleap</h1>
                    <p class="text-xs text-charcoal/60">Shop Smart. Shop Simple.</p>
                </div>

            </a>

            <div class="flex items-center gap-2 text-sm">
                <span class="hidden text-charcoal/60 sm:inline">Already have an account?</span>
                <a href="{{ route('login') }}" class="font-semibold text-primary transition hover:text-sky-blue">
                    Log in
                </a>
            </div>

        </div>

    </header>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="flex min-h-[calc(100vh-9rem)] items-center justify-center px-4 py-12 sm:px-6">

        <div class="mx-auto w-full max-w-3xl">

            <div class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-xl shadow-charcoal/5">

                <!-- CARD HEADER -->
                <div class="px-6 pb-2 pt-8 text-center sm:px-10">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/20">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3h18M4 3l1.5 9.5A2 2 0 007.48 14.5h9.04a2 2 0 001.98-2L20 3M9 19a1 1 0 11-2 0 1 1 0 012 0zm10 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>

                    <h2 class="mt-5 text-2xl font-bold text-charcoal sm:text-3xl">Seller Registration</h2>
                    <p class="mt-2 text-sm leading-6 text-charcoal/60">
                        Set up your seller account to start listing products on Shopleap.
                    </p>

                </div>

                <!-- =================================================
                     FORM
                ================================================== -->

                <form method="POST" action="{{ route('register.seller') }}" enctype="multipart/form-data"
                    class="px-6 pb-10 pt-7 sm:px-10">

                    @csrf

                    <!-- VALIDATION ERRORS -->
                    @if ($errors->any())
                        <div class="mb-6 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3">
                            <p class="text-sm font-semibold text-sale-red">Please review the fields below.</p>
                            <ul class="mt-1 list-inside list-disc text-xs text-sale-red/80">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    <!-- =================================================
                         SECTION: PERSONAL INFORMATION
                    ================================================== -->

                    <p class="mb-4 text-xs font-bold uppercase tracking-wide text-primary">Personal Information</p>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Last name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">First name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Middle initial</label>
                            <input type="text" name="middle_initial" maxlength="5" value="{{ old('middle_initial') }}"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Sex *</label>
                            <select name="sex" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <option value="" disabled {{ old('sex') ? '' : 'selected' }}>Select</option>
                                <option value="male" {{ old('sex') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('sex') === 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">E-mail *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Contact No. *</label>
                            <input type="tel" name="contact_no" value="{{ old('contact_no') }}" required
                                placeholder="09XXXXXXXXX"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Birthday *</label>
                            <input type="date" id="birthday" name="birthday" value="{{ old('birthday') }}" required
                                max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Age</label>
                            <input type="text" id="age" readonly placeholder="Auto-calculated"
                                class="w-full cursor-not-allowed rounded-xl border border-light-gray bg-ice-blue px-4 py-3 text-sm text-charcoal/60 outline-none">
                        </div>

                    </div>


                    <!-- =================================================
                         SECTION: ADDRESS
                    ================================================== -->

                    <p class="mb-4 mt-8 text-xs font-bold uppercase tracking-wide text-primary">Address</p>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Province *</label>
                            <select id="province" name="province" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <option value="">Loading provinces...</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Municipality/City *</label>
                            <select id="municipality" name="municipality" required disabled
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10 disabled:bg-ice-blue disabled:text-charcoal/40">
                                <option value="">Select province first</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Barangay *</label>
                            <select id="barangay" name="barangay" required disabled
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10 disabled:bg-ice-blue disabled:text-charcoal/40">
                                <option value="">Select municipality first</option>
                            </select>
                        </div>

                    </div>

                    <div class="mt-5">
                        <label class="mb-2 block text-sm font-semibold text-charcoal">Street / House No. / Building, etc. *</label>
                        <input type="text" name="street_address" value="{{ old('street_address') }}" required
                            placeholder="e.g. 123 Rizal St., Purok 4"
                            class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>


                    <!-- =================================================
                         SECTION: BUSINESS INFORMATION
                    ================================================== -->

                    <p class="mb-4 mt-8 text-xs font-bold uppercase tracking-wide text-primary">Business Information</p>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Business name *</label>
                            <input type="text" name="business_name" value="{{ old('business_name') }}" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Line of business (category) *</label>
                            <select name="line_of_business" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <option value="" disabled {{ old('line_of_business') ? '' : 'selected' }}>Select category</option>
                                @foreach (['Fashion & Apparel', 'Electronics', 'Food & Beverage', 'Health & Beauty', 'Home & Living', 'Automotive', 'Sports & Outdoors', 'Books & Hobbies', 'Others'] as $category)
                                    <option value="{{ $category }}" {{ old('line_of_business') === $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>


                    <!-- =================================================
                         SECTION: DOCUMENTS
                    ================================================== -->

                    <p class="mb-4 mt-8 text-xs font-bold uppercase tracking-wide text-primary">Document Upload</p>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Upload valid ID *</label>
                            <input type="file" name="id_upload" required accept=".jpg,.jpeg,.png,.pdf"
                                class="w-full rounded-xl border border-dashed border-light-gray bg-ice-blue/50 px-4 py-3 text-sm text-charcoal/70 outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                            <p class="mt-1 text-xs text-charcoal/45">JPG, PNG or PDF. Max 5MB.</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Upload business permit *</label>
                            <input type="file" name="business_permit" required accept=".jpg,.jpeg,.png,.pdf"
                                class="w-full rounded-xl border border-dashed border-light-gray bg-ice-blue/50 px-4 py-3 text-sm text-charcoal/70 outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                            <p class="mt-1 text-xs text-charcoal/45">JPG, PNG or PDF. Max 5MB.</p>
                        </div>

                    </div>


                    <!-- =================================================
                         SECTION: ACCOUNT SECURITY
                    ================================================== -->

                    <p class="mb-4 mt-8 text-xs font-bold uppercase tracking-wide text-primary">Account Security</p>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Password *</label>
                            <input type="password" name="password" required minlength="8"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Confirm password *</label>
                            <input type="password" name="password_confirmation" required minlength="8"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>

                    </div>


                    <!-- =================================================
                         NOTICE
                    ================================================== -->

                    <div class="mt-7 rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3">
                        <p class="text-xs leading-5 text-leaf-green">
                            After submitting your registration, please wait for the administrator's approval,
                            which will be sent to your email.
                        </p>
                    </div>


                    <!-- =================================================
                         SUBMIT
                    ================================================== -->

                    <button type="submit"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/20">
                        Submit Registration
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6" />
                        </svg>
                    </button>

                </form>

            </div>

        </div>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="border-t border-light-gray bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-5 py-6 sm:flex-row">
            <p class="text-xs text-charcoal/60">© {{ date('Y') }} Shopleap. All rights reserved.</p>
            <p class="text-xs text-charcoal/60">Shop Smart. Shop Simple.</p>
        </div>
    </footer>


    <!-- =====================================================
         SCRIPTS
    ====================================================== -->

    <script>
        // ---------------------------------------------------
        // AGE AUTO-CALCULATION
        // ---------------------------------------------------
        const birthdayInput = document.getElementById('birthday');
        const ageInput = document.getElementById('age');

        function calculateAge(birthdateStr) {
            const today = new Date();
            const birth = new Date(birthdateStr);
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        }

        birthdayInput.addEventListener('change', function() {
            if (this.value) {
                ageInput.value = calculateAge(this.value) + ' years old';
            }
        });

        // ---------------------------------------------------
        // CASCADING ADDRESS DROPDOWNS (PSGC API)
        // Docs: https://psgc.gitlab.io/api/
        // NOTE: verify this endpoint/response shape in your own
        // browser before relying on it — response fields below
        // (`name`, `code`) match the documented PSGC API, but
        // confirm since this was not live-tested in this session.
        // ---------------------------------------------------
        const PSGC_BASE = 'https://psgc.gitlab.io/api';

        const provinceSelect = document.getElementById('province');
        const municipalitySelect = document.getElementById('municipality');
        const barangaySelect = document.getElementById('barangay');

        async function loadProvinces() {
            try {
                const res = await fetch(`${PSGC_BASE}/provinces/`);
                const data = await res.json();

                provinceSelect.innerHTML = '<option value="" disabled selected>Select province</option>';
                data
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.name;
                        opt.dataset.code = p.code;
                        opt.textContent = p.name;
                        provinceSelect.appendChild(opt);
                    });
            } catch (err) {
                provinceSelect.innerHTML = '<option value="">Could not load — enter manually below</option>';
                console.error('Failed to load provinces:', err);
            }
        }

        async function loadMunicipalities(provinceCode) {
            municipalitySelect.disabled = true;
            municipalitySelect.innerHTML = '<option value="">Loading...</option>';
            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select municipality first</option>';

            try {
                const res = await fetch(`${PSGC_BASE}/provinces/${provinceCode}/cities-municipalities/`);
                const data = await res.json();

                municipalitySelect.innerHTML = '<option value="" disabled selected>Select municipality/city</option>';
                data
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.name;
                        opt.dataset.code = m.code;
                        opt.textContent = m.name;
                        municipalitySelect.appendChild(opt);
                    });
                municipalitySelect.disabled = false;
            } catch (err) {
                municipalitySelect.innerHTML = '<option value="">Could not load</option>';
                console.error('Failed to load municipalities:', err);
            }
        }

        async function loadBarangays(municipalityCode) {
            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Loading...</option>';

            try {
                const res = await fetch(`${PSGC_BASE}/cities-municipalities/${municipalityCode}/barangays/`);
                const data = await res.json();

                barangaySelect.innerHTML = '<option value="" disabled selected>Select barangay</option>';
                data
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b.name;
                        opt.textContent = b.name;
                        barangaySelect.appendChild(opt);
                    });
                barangaySelect.disabled = false;
            } catch (err) {
                barangaySelect.innerHTML = '<option value="">Could not load</option>';
                console.error('Failed to load barangays:', err);
            }
        }

        provinceSelect.addEventListener('change', function() {
            const code = this.options[this.selectedIndex].dataset.code;
            if (code) loadMunicipalities(code);
        });

        municipalitySelect.addEventListener('change', function() {
            const code = this.options[this.selectedIndex].dataset.code;
            if (code) loadBarangays(code);
        });

        loadProvinces();
    </script>

</body>

</html>
