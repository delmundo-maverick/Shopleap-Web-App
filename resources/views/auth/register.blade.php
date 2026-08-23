<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Create Account | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="border-b border-light-gray bg-white">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">

            <!-- Logo / Brand -->

            <a href="{{ route('home') }}" class="flex items-center gap-3">

                <!-- Logo Placeholder -->
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-ice-blue">
                    <!--
                    Add your logo later:

                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Shopleap Logo"
                        class="h-full w-full rounded-xl object-contain"
                    >
                    -->
                </div>

                <div>
                    <h1 class="text-lg font-bold leading-tight text-primary">
                        Shopleap
                    </h1>

                    <p class="text-xs text-charcoal/60">
                        Shop Smart. Shop Simple.
                    </p>
                </div>

            </a>


            <!-- Login -->

            <div class="flex items-center gap-2 text-sm">

                <span class="hidden text-charcoal/60 sm:inline">
                    Already have an account?
                </span>

                <a
                    href="{{ route('login') }}"
                    class="font-semibold text-primary transition hover:text-sky-blue"
                >
                    Log in
                </a>

            </div>

        </div>
    </header>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="px-4 py-10 sm:px-6 lg:py-14">

        <div class="mx-auto max-w-4xl">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="mb-8 text-center">

                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/20"
                >

                    <svg
                        class="h-7 w-7 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5.5 21a6.5 6.5 0 0113 0"
                        />
                    </svg>

                </div>


                <h2 class="mt-5 text-3xl font-bold tracking-tight text-charcoal sm:text-4xl">
                    Create your Shopleap account
                </h2>


                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-charcoal/70 sm:text-base">
                    Create your buyer account to start shopping on Shopleap.
                    You can apply to become a seller later from your account.
                </p>

            </div>


            <!-- =================================================
                 REGISTRATION CARD
            ================================================== -->

            <div class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-xl shadow-charcoal/5">


                <!-- Card Header -->

                <div class="border-b border-light-gray bg-white px-6 py-6 sm:px-8">

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary"
                        >

                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 21a8 8 0 0116 0"
                                />
                            </svg>

                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-charcoal">
                                Buyer Registration
                            </h3>

                            <p class="text-sm text-charcoal/60">
                                Please provide your information below.
                            </p>
                        </div>

                    </div>

                </div>


                <!-- Form -->

                <form
                    method="POST"
                    action="#"
                    enctype="multipart/form-data"
                    class="px-6 py-7 sm:px-8"
                >

                    @csrf


                    <!-- =================================================
                         PERSONAL INFORMATION
                    ================================================== -->

                    <div>

                        <div class="mb-5 flex items-center gap-3">

                            <div class="h-px flex-1 bg-light-gray"></div>

                            <span
                                class="text-xs font-bold uppercase tracking-wider text-primary"
                            >
                                Personal Information
                            </span>

                            <div class="h-px flex-1 bg-light-gray"></div>

                        </div>


                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">


                            <!-- Last Name -->

                            <div>
                                <label
                                    for="last_name"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Last Name
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="last_name"
                                    name="last_name"
                                    value="{{ old('last_name') }}"
                                    required
                                    autocomplete="family-name"
                                    placeholder="Enter last name"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >
                            </div>


                            <!-- First Name -->

                            <div>
                                <label
                                    for="first_name"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    First Name
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    value="{{ old('first_name') }}"
                                    required
                                    autocomplete="given-name"
                                    placeholder="Enter first name"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >
                            </div>


                            <!-- Middle Initial -->

                            <div>
                                <label
                                    for="middle_initial"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Middle Initial
                                    <span class="font-normal text-charcoal/50">(Optional)</span>
                                </label>

                                <input
                                    type="text"
                                    id="middle_initial"
                                    name="middle_initial"
                                    value="{{ old('middle_initial') }}"
                                    maxlength="2"
                                    placeholder="e.g. D."
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >
                            </div>


                            <!-- Sex -->

                            <div>
                                <label
                                    for="sex"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Sex
                                    <span class="text-sale-red">*</span>
                                </label>

                                <select
                                    id="sex"
                                    name="sex"
                                    required
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >

                                    <option value="" disabled selected>
                                        Select sex
                                    </option>

                                    <option value="male">
                                        Male
                                    </option>

                                    <option value="female">
                                        Female
                                    </option>

                                </select>
                            </div>


                            <!-- Birthday -->

                            <div>
                                <label
                                    for="birthday"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Birthday
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="date"
                                    id="birthday"
                                    name="birthday"
                                    value="{{ old('birthday') }}"
                                    required
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >
                            </div>


                            <!-- Age -->

                            <div>
                                <label
                                    for="age"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Age
                                    <span class="text-xs font-normal text-charcoal/50">
                                        (Automatically calculated)
                                    </span>
                                </label>

                                <input
                                    type="number"
                                    id="age"
                                    name="age"
                                    value="{{ old('age') }}"
                                    readonly
                                    placeholder="Auto-generated"
                                    class="w-full cursor-not-allowed rounded-xl border border-light-gray bg-light-gray px-4 py-3 text-sm text-charcoal/70 outline-none"
                                >
                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         CONTACT INFORMATION
                    ================================================== -->

                    <div class="mt-9">

                        <div class="mb-5 flex items-center gap-3">

                            <div class="h-px flex-1 bg-light-gray"></div>

                            <span
                                class="text-xs font-bold uppercase tracking-wider text-primary"
                            >
                                Contact Information
                            </span>

                            <div class="h-px flex-1 bg-light-gray"></div>

                        </div>


                        <div class="grid gap-5 sm:grid-cols-2">


                            <!-- Email -->

                            <div>
                                <label
                                    for="email"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    E-mail
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="email"
                                    placeholder="you@example.com"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >
                            </div>


                            <!-- Contact Number -->

                            <div>
                                <label
                                    for="contact_no"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Contact No.
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="tel"
                                    id="contact_no"
                                    name="contact_no"
                                    value="{{ old('contact_no') }}"
                                    required
                                    autocomplete="tel"
                                    placeholder="09XX XXX XXXX"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >
                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         ADDRESS
                    ================================================== -->

                    <div class="mt-9">

                        <div class="mb-2 flex items-center gap-3">

                            <div class="h-px flex-1 bg-light-gray"></div>

                            <span
                                class="text-xs font-bold uppercase tracking-wider text-primary"
                            >
                                Address
                            </span>

                            <div class="h-px flex-1 bg-light-gray"></div>

                        </div>


                        <p class="mb-5 text-xs text-charcoal/60">
                            Province, municipality, and barangay will be
                            selected through the address API.
                        </p>


                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">


                            <!-- Province -->

                            <div>
                                <label
                                    for="province"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Province
                                    <span class="text-sale-red">*</span>
                                </label>

                                <select
                                    id="province"
                                    name="province"
                                    required
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >
                                    <option value="">
                                        Select province
                                    </option>
                                </select>
                            </div>


                            <!-- Municipality -->

                            <div>
                                <label
                                    for="municipality"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Municipality
                                    <span class="text-sale-red">*</span>
                                </label>

                                <select
                                    id="municipality"
                                    name="municipality"
                                    required
                                    disabled
                                    class="w-full rounded-xl border border-light-gray bg-light-gray px-4 py-3 text-sm text-charcoal/60 outline-none"
                                >
                                    <option value="">
                                        Select municipality
                                    </option>
                                </select>
                            </div>


                            <!-- Barangay -->

                            <div>
                                <label
                                    for="barangay"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Barangay
                                    <span class="text-sale-red">*</span>
                                </label>

                                <select
                                    id="barangay"
                                    name="barangay"
                                    required
                                    disabled
                                    class="w-full rounded-xl border border-light-gray bg-light-gray px-4 py-3 text-sm text-charcoal/60 outline-none"
                                >
                                    <option value="">
                                        Select barangay
                                    </option>
                                </select>
                            </div>


                            <!-- Street -->

                            <div class="sm:col-span-2 lg:col-span-2">

                                <label
                                    for="street"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Street
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="street"
                                    name="street"
                                    value="{{ old('street') }}"
                                    required
                                    placeholder="Street name"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >

                            </div>


                            <!-- House Number -->

                            <div>

                                <label
                                    for="house_number"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    House Number
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="house_number"
                                    name="house_number"
                                    value="{{ old('house_number') }}"
                                    required
                                    placeholder="House / Unit No."
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         ID
                    ================================================== -->

                    <div class="mt-9">

                        <div class="mb-5 flex items-center gap-3">

                            <div class="h-px flex-1 bg-light-gray"></div>

                            <span
                                class="text-xs font-bold uppercase tracking-wider text-primary"
                            >
                                Identity Verification
                            </span>

                            <div class="h-px flex-1 bg-light-gray"></div>

                        </div>


                        <div
                            class="rounded-2xl border border-dashed border-primary/30 bg-ice-blue/50 p-5"
                        >

                            <label
                                for="id_document"
                                class="block cursor-pointer"
                            >

                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-primary shadow-sm"
                                    >

                                        <svg
                                            class="h-6 w-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M12 16V4m0 0L8 8m4-4l4 4M5 20h14"
                                            />
                                        </svg>

                                    </div>


                                    <div class="flex-1">

                                        <p class="text-sm font-bold text-charcoal">
                                            Upload a valid ID
                                            <span class="text-sale-red">*</span>
                                        </p>

                                        <p class="mt-1 text-xs leading-5 text-charcoal/60">
                                            Upload a clear image or PDF of your valid
                                            government-issued ID.
                                        </p>

                                    </div>


                                    <span
                                        class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white"
                                    >
                                        Choose File
                                    </span>

                                </div>

                            </label>


                            <input
                                type="file"
                                id="id_document"
                                name="id_document"
                                required
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="sr-only"
                            >

                            <p
                                id="file-name"
                                class="mt-3 hidden text-xs font-semibold text-leaf-green"
                            ></p>

                        </div>

                    </div>


                    <!-- =================================================
                         PASSWORD
                    ================================================== -->

                    <div class="mt-9">

                        <div class="mb-5 flex items-center gap-3">

                            <div class="h-px flex-1 bg-light-gray"></div>

                            <span
                                class="text-xs font-bold uppercase tracking-wider text-primary"
                            >
                                Account Security
                            </span>

                            <div class="h-px flex-1 bg-light-gray"></div>

                        </div>


                        <div class="grid gap-5 sm:grid-cols-2">


                            <!-- Password -->

                            <div>

                                <label
                                    for="password"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Password
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Create a password"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >

                            </div>


                            <!-- Confirm Password -->

                            <div>

                                <label
                                    for="password_confirmation"
                                    class="mb-2 block text-sm font-semibold text-charcoal"
                                >
                                    Confirm Password
                                    <span class="text-sale-red">*</span>
                                </label>

                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Confirm your password"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >

                            </div>

                        </div>


                        <p class="mt-3 text-xs text-charcoal/50">
                            Use at least 8 characters with a combination of
                            letters, numbers, and symbols.
                        </p>

                    </div>


                    <!-- =================================================
                         APPROVAL NOTICE
                    ================================================== -->

                    <div
                        class="mt-8 flex items-start gap-3 rounded-xl border border-primary/10 bg-ice-blue p-4"
                    >

                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0 text-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 9v3m0 4h.01M10.3 3.8L2.9 17a2 2 0 001.75 3h14.7a2 2 0 001.75-3L13.7 3.8a2 2 0 00-3.4 0z"
                            />

                        </svg>


                        <div>

                            <p class="text-sm font-bold text-charcoal">
                                Administrator approval required
                            </p>

                            <p class="mt-1 text-xs leading-5 text-charcoal/65">
                                After submitting your registration, your
                                application will be reviewed by a Shopleap
                                administrator. You will receive an email
                                notification regarding the decision.
                            </p>

                        </div>

                    </div>


                    <!-- =================================================
                         SUBMIT
                    ================================================== -->

                    <div class="mt-8">

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/20"
                        >

                            Create Buyer Account

                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 12h14m-6-6l6 6-6 6"
                                />

                            </svg>

                        </button>

                    </div>


                    <!-- Terms -->

                    <p class="mt-4 text-center text-xs leading-5 text-charcoal/50">
                        By creating an account, you agree to Shopleap's
                        terms and policies.
                    </p>

                </form>

            </div>


            <!-- =================================================
                 LOGIN
            ================================================== -->

            <div class="mt-7 text-center">

                <p class="text-sm text-charcoal/65">
                    Already have a Shopleap account?

                    <a
                        href="{{ route('login') }}"
                        class="font-bold text-primary transition hover:text-sky-blue hover:underline"
                    >
                        Log in
                    </a>
                </p>

            </div>

        </div>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="border-t border-light-gray bg-white">

        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-5 py-6 sm:flex-row">

            <p class="text-xs text-charcoal/60">
                © {{ date('Y') }} Shopleap. All rights reserved.
            </p>

            <p class="text-xs text-charcoal/60">
                Shop Smart. Shop Simple.
            </p>

        </div>

    </footer>


    <!-- =====================================================
         SMALL CLIENT-SIDE HELPERS
    ====================================================== -->

    <script>

        /*
        |--------------------------------------------------------------------------
        | Automatically Calculate Age
        |--------------------------------------------------------------------------
        */

        const birthdayInput = document.getElementById('birthday');
        const ageInput = document.getElementById('age');

        birthdayInput.addEventListener('change', function () {

            if (!this.value) {
                ageInput.value = '';
                return;
            }

            const birthday = new Date(this.value);
            const today = new Date();

            let age = today.getFullYear() - birthday.getFullYear();

            const monthDifference =
                today.getMonth() - birthday.getMonth();

            if (
                monthDifference < 0 ||
                (
                    monthDifference === 0 &&
                    today.getDate() < birthday.getDate()
                )
            ) {
                age--;
            }

            ageInput.value = age;
        });


        /*
        |--------------------------------------------------------------------------
        | Display Selected ID File
        |--------------------------------------------------------------------------
        */

        const idInput = document.getElementById('id_document');
        const fileName = document.getElementById('file-name');

        idInput.addEventListener('change', function () {

            if (this.files.length > 0) {

                fileName.textContent =
                    'Selected file: ' + this.files[0].name;

                fileName.classList.remove('hidden');

            } else {

                fileName.textContent = '';
                fileName.classList.add('hidden');

            }

        });

    </script>

</body>

</html>
