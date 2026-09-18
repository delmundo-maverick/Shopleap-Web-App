<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Logistics / Sorting Center Registration | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

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
                <a href="{{ route('login') }}" class="font-semibold text-primary transition hover:text-sky-blue">Log
                    in</a>
            </div>
        </div>
    </header>

    <main class="px-4 py-10 sm:px-6 lg:py-14">
        <div class="mx-auto max-w-4xl">

            <div class="mb-8 text-center">
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/20">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8" />
                    </svg>
                </div>
                <h2 class="mt-5 text-3xl font-bold tracking-tight text-charcoal">Logistics / Sorting Center Registration
                </h2>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-charcoal/65">
                    Register your sorting center to manage parcel routing and rider assignments on Shopleap.
                </p>
            </div>

            <form method="POST" action="{{ route('register.logistics') }}" enctype="multipart/form-data"
                class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-xl shadow-charcoal/5">

                @csrf

                @if ($errors->any())
                    <div class="mx-6 mt-6 rounded-xl border border-sale-red/20 bg-sale-red/10 p-4 sm:mx-8">
                        <p class="text-sm font-semibold text-sale-red">Please correct the following errors:</p>
                        <ul class="mt-1 list-inside list-disc text-xs text-sale-red/80">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- ACCOUNT INFORMATION -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <h3 class="mb-6 text-lg font-bold text-charcoal">Account Information</h3>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-charcoal">E-mail *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Password *</label>
                            <input type="password" name="password" required minlength="8"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Confirm Password *</label>
                            <input type="password" name="password_confirmation" required minlength="8"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                    </div>
                </section>

                <!-- PERSONAL INFORMATION -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <h3 class="mb-6 text-lg font-bold text-charcoal">Personal Information</h3>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Middle Initial</label>
                            <input type="text" name="middle_initial" maxlength="1"
                                value="{{ old('middle_initial') }}"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm uppercase outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Sex *</label>
                            <select name="sex" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <option value="" disabled {{ old('sex') ? '' : 'selected' }}>Select sex</option>
                                <option value="male" {{ old('sex') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('sex') === 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Birthday *</label>
                            <input type="date" id="birthday" name="birthday" value="{{ old('birthday') }}" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Age</label>
                            <input type="text" id="age" readonly placeholder="Auto-calculated"
                                class="w-full cursor-not-allowed rounded-xl border border-light-gray bg-ice-blue px-4 py-3.5 text-sm text-charcoal/60 outline-none">
                        </div>
                        <div class="md:col-span-3">
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Contact No. *</label>
                            <input type="tel" name="contact_no" value="{{ old('contact_no') }}" required
                                placeholder="09XXXXXXXXX"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                    </div>
                </section>

                <!-- ADDRESS -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <h3 class="mb-6 text-lg font-bold text-charcoal">Address</h3>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Province *</label>
                            <select id="province" name="province" required
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <option value="">Loading provinces...</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Municipality/City *</label>
                            <select id="municipality" name="municipality" required disabled
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10 disabled:bg-ice-blue disabled:text-charcoal/40">
                                <option value="">Select province first</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Barangay *</label>
                            <select id="barangay" name="barangay" required disabled
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10 disabled:bg-ice-blue disabled:text-charcoal/40">
                                <option value="">Select municipality first</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="mb-2 block text-sm font-semibold text-charcoal">Street / House No. / Building,
                            etc. *</label>
                        <input type="text" name="street_address" value="{{ old('street_address') }}" required
                            class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>
                </section>

                <!-- BUSINESS INFORMATION -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <h3 class="mb-6 text-lg font-bold text-charcoal">Business Information</h3>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-charcoal">Business Name *</label>
                        <input type="text" name="business_name" value="{{ old('business_name') }}" required
                            placeholder="e.g. Laguna Central Sorting Hub"
                            class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>
                </section>

                <!-- DOCUMENTS -->
                <section class="border-b border-light-gray px-6 py-7 sm:px-8">
                    <h3 class="mb-6 text-lg font-bold text-charcoal">Document Upload</h3>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Upload Valid ID *</label>
                            <input type="file" name="id_upload" required accept=".jpg,.jpeg,.png,.pdf"
                                class="w-full rounded-xl border border-dashed border-light-gray bg-ice-blue/50 px-4 py-3 text-sm text-charcoal/70 outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                            <p class="mt-1 text-xs text-charcoal/45">JPG, PNG or PDF. Max 5MB.</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-charcoal">Upload Business/DTI Permit
                                *</label>
                            <input type="file" name="business_permit" required accept=".jpg,.jpeg,.png,.pdf"
                                class="w-full rounded-xl border border-dashed border-light-gray bg-ice-blue/50 px-4 py-3 text-sm text-charcoal/70 outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                            <p class="mt-1 text-xs text-charcoal/45">JPG, PNG or PDF. Max 5MB.</p>
                        </div>
                    </div>
                </section>

                <!-- APPROVAL NOTICE -->
                <section class="px-6 py-7 sm:px-8">
                    <div class="rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3">
                        <p class="text-xs leading-5 text-leaf-green">
                            After submitting your registration, please wait for the administrator's approval,
                            which will be sent to your email.
                        </p>
                    </div>

                    <div class="mt-6 flex items-start gap-3">
                        <input type="checkbox" name="terms" required
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-light-gray accent-primary focus:ring-primary/20">
                        <label class="text-xs leading-5 text-charcoal/65">
                            I confirm that the information I provided is accurate and complete, and I agree to the
                            Shopleap terms and policies.
                            <span class="text-sale-red">*</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-4 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue hover:shadow-xl">
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

        </div>
    </main>

    <footer class="border-t border-light-gray bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-5 py-6 sm:flex-row">
            <p class="text-xs text-charcoal/60">© {{ date('Y') }} Shopleap. All rights reserved.</p>
            <p class="text-xs text-charcoal/60">Shop Smart. Shop Simple.</p>
        </div>
    </footer>

    <script>
        // Age auto-calculation
        const birthdayInput = document.getElementById('birthday');
        const ageInput = document.getElementById('age');
        birthdayInput.addEventListener('change', function() {
            if (!this.value) {
                ageInput.value = '';
                return;
            }
            const birth = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
            ageInput.value = age >= 0 ? age : '';
        });

        // Cascading address dropdowns (PSGC API) — same pattern as Seller/Buyer registration
        const PSGC_BASE = 'https://psgc.gitlab.io/api';
        const provinceSelect = document.getElementById('province');
        const municipalitySelect = document.getElementById('municipality');
        const barangaySelect = document.getElementById('barangay');

        async function loadProvinces() {
            try {
                const res = await fetch(`${PSGC_BASE}/provinces/`);
                const data = await res.json();
                provinceSelect.innerHTML = '<option value="" disabled selected>Select province</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.name;
                    opt.dataset.code = p.code;
                    opt.textContent = p.name;
                    provinceSelect.appendChild(opt);
                });
            } catch (err) {
                provinceSelect.innerHTML = '<option value="">Could not load — check your connection</option>';
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
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.name;
                    opt.dataset.code = m.code;
                    opt.textContent = m.name;
                    municipalitySelect.appendChild(opt);
                });
                municipalitySelect.disabled = false;
            } catch (err) {
                municipalitySelect.innerHTML = '<option value="">Could not load</option>';
            }
        }

        async function loadBarangays(municipalityCode) {
            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Loading...</option>';
            try {
                const res = await fetch(`${PSGC_BASE}/cities-municipalities/${municipalityCode}/barangays/`);
                const data = await res.json();
                barangaySelect.innerHTML = '<option value="" disabled selected>Select barangay</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.name;
                    opt.textContent = b.name;
                    barangaySelect.appendChild(opt);
                });
                barangaySelect.disabled = false;
            } catch (err) {
                barangaySelect.innerHTML = '<option value="">Could not load</option>';
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
