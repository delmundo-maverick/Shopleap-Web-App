<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Buyer Registration | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="border-b border-light-gray bg-white">

        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">

            <!-- BRAND -->

            <a href="{{ route('home') }}" class="flex items-center gap-3">

                <!-- LOGO PLACEHOLDER -->

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ice-blue">

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


            <!-- LOGIN -->

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
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5 21a7 7 0 0114 0"
                        />

                    </svg>

                </div>


                <h2 class="mt-5 text-3xl font-bold tracking-tight text-charcoal">
                    Buyer Registration
                </h2>


                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-charcoal/65">
                    Create your Shopleap Buyer account and start shopping
                    from our marketplace.
                </p>

            </div>


            <!-- =================================================
                 REGISTRATION FORM
            ================================================== -->

            <form
                method="POST"
                action="#"
                enctype="multipart/form-data"
                class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-xl shadow-charcoal/5"
            >

                @csrf


                <!-- =================================================
                     FORM ERROR MESSAGE
                ================================================== -->

                @if ($errors->any())

                    <div class="mx-6 mt-6 rounded-xl border border-sale-red/20 bg-sale-red/10 p-4 sm:mx-8">

                        <div class="flex items-start gap-3">

                            <svg
                                class="mt-0.5 h-5 w-5 shrink-0 text-sale-red"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v3m0 4h.01M10.3 3.8L2.9 17a2 2 0 001.75 3h14.7a2 2 0 001.75-3L13.7 3.8a2 2 0 00-3.4 0z"
                                />

                            </svg>

                            <div>

                                <p class="text-sm font-semibold text-sale-red">
                                    Please correct the following errors:
                                </p>

                                <ul class="mt-1 list-inside list-disc text-xs text-sale-red/80">

                                    @foreach ($errors->all() as $error)

                                        <li>{{ $error }}</li>

                                    @endforeach

                                </ul>

                            </div>

                        </div>

                    </div>

                @endif


                <!-- =================================================
                     ACCOUNT INFORMATION
                ================================================== -->

                <section class="border-b border-light-gray px-6 py-7 sm:px-8">

                    <div class="mb-6 flex items-start gap-4">

                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary"
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
                                    d="M15 7a3 3 0 11-6 0 3 3 0 016 0zM5 21a7 7 0 0114 0M17 11h4m-2-2v4"
                                />

                            </svg>

                        </div>

                        <div>

                            <h3 class="text-lg font-bold text-charcoal">
                                Account Information
                            </h3>

                            <p class="mt-1 text-xs text-charcoal/55">
                                These details will be used to access your Shopleap account.
                            </p>

                        </div>

                    </div>


                    <div class="grid gap-5 md:grid-cols-2">

                        <!-- EMAIL -->

                        <div class="md:col-span-2">

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
                                placeholder="Enter your e-mail address"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                        </div>


                        <!-- PASSWORD -->

                        <div>

                            <label
                                for="password"
                                class="mb-2 block text-sm font-semibold text-charcoal"
                            >
                                Password
                                <span class="text-sale-red">*</span>
                            </label>

                            <div class="relative">

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Create a password"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 pr-11 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >

                                <button
                                    type="button"
                                    onclick="togglePassword('password', 'passwordEye')"
                                    class="absolute inset-y-0 right-0 flex items-center px-4 text-charcoal/40 hover:text-primary"
                                >

                                    <svg
                                        id="passwordEye"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"
                                        />

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="2.5"
                                            stroke-width="1.8"
                                        />

                                    </svg>

                                </button>

                            </div>

                        </div>


                        <!-- CONFIRM PASSWORD -->

                        <div>

                            <label
                                for="password_confirmation"
                                class="mb-2 block text-sm font-semibold text-charcoal"
                            >
                                Confirm Password
                                <span class="text-sale-red">*</span>
                            </label>

                            <div class="relative">

                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Confirm your password"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 pr-11 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                                >

                                <button
                                    type="button"
                                    onclick="togglePassword('password_confirmation', 'confirmPasswordEye')"
                                    class="absolute inset-y-0 right-0 flex items-center px-4 text-charcoal/40 hover:text-primary"
                                >

                                    <svg
                                        id="confirmPasswordEye"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"
                                        />

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="2.5"
                                            stroke-width="1.8"
                                        />

                                    </svg>

                                </button>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     PERSONAL INFORMATION
                ================================================== -->

                <section class="border-b border-light-gray px-6 py-7 sm:px-8">

                    <div class="mb-6 flex items-start gap-4">

                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary"
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
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5 21a7 7 0 0114 0"
                                />

                            </svg>

                        </div>

                        <div>

                            <h3 class="text-lg font-bold text-charcoal">
                                Personal Information
                            </h3>

                            <p class="mt-1 text-xs text-charcoal/55">
                                Enter your personal details exactly as they appear on your identification document.
                            </p>

                        </div>

                    </div>


                    <div class="grid gap-5 md:grid-cols-3">

                        <!-- LAST NAME -->

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
                                placeholder="Last name"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                        </div>


                        <!-- FIRST NAME -->

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
                                placeholder="First name"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                        </div>


                        <!-- MIDDLE INITIAL -->

                        <div>

                            <label
                                for="middle_initial"
                                class="mb-2 block text-sm font-semibold text-charcoal"
                            >
                                Middle Initial
                                <span class="text-charcoal/40">(Optional)</span>
                            </label>

                            <input
                                type="text"
                                id="middle_initial"
                                name="middle_initial"
                                value="{{ old('middle_initial') }}"
                                maxlength="1"
                                placeholder="M"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm uppercase outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                        </div>


                        <!-- SEX -->

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
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                                <option value="" disabled selected>
                                    Select sex
                                </option>

                                <option
                                    value="male"
                                    {{ old('sex') === 'male' ? 'selected' : '' }}
                                >
                                    Male
                                </option>

                                <option
                                    value="female"
                                    {{ old('sex') === 'female' ? 'selected' : '' }}
                                >
                                    Female
                                </option>

                            </select>

                        </div>


                        <!-- BIRTHDAY -->

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
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                        </div>


                        <!-- AGE -->

                        <div>

                            <label
                                for="age"
                                class="mb-2 block text-sm font-semibold text-charcoal"
                            >
                                Age
                                <span class="text-sale-red">*</span>
                            </label>

                            <input
                                type="number"
                                id="age"
                                name="age"
                                value="{{ old('age') }}"
                                readonly
                                placeholder="Auto-generated"
                                class="w-full cursor-not-allowed rounded-xl border border-light-gray bg-light-gray px-4 py-3.5 text-sm text-charcoal/70 outline-none"
                            >

                            <p class="mt-1.5 text-xs text-charcoal/45">
                                Automatically calculated from your birthday.
                            </p>

                        </div>


                        <!-- CONTACT NUMBER -->

                        <div class="md:col-span-3">

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
                                placeholder="e.g. 09171234567"
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     ADDRESS
                ================================================== -->

                <section class="border-b border-light-gray px-6 py-7 sm:px-8">

                    <div class="mb-6 flex items-start gap-4">

                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary"
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
                                    d="M12 21s7-5.2 7-11a7 7 0 10-14 0c0 5.8 7 11 7 11z"
                                />

                                <circle
                                    cx="12"
                                    cy="10"
                                    r="2.5"
                                    stroke-width="1.8"
                                />

                            </svg>

                        </div>

                        <div>

                            <h3 class="text-lg font-bold text-charcoal">
                                Address
                            </h3>

                            <p class="mt-1 text-xs text-charcoal/55">
                                Select your location and enter your complete delivery address.
                            </p>

                        </div>

                    </div>


                    <div class="grid gap-5 md:grid-cols-3">

                        <!-- PROVINCE -->

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
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                                <option value="">
                                    Select province
                                </option>

                                @if(old('province'))

                                    <option value="{{ old('province') }}" selected>
                                        {{ old('province') }}
                                    </option>

                                @endif

                            </select>

                            <p class="mt-1.5 text-xs text-charcoal/45">
                                API location data
                            </p>

                        </div>


                        <!-- MUNICIPALITY -->

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
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10 disabled:cursor-not-allowed disabled:bg-light-gray"
                            >

                                <option value="">
                                    Select municipality
                                </option>

                            </select>

                            <p class="mt-1.5 text-xs text-charcoal/45">
                                Select a province first
                            </p>

                        </div>


                        <!-- BARANGAY -->

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
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm text-charcoal outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10 disabled:cursor-not-allowed disabled:bg-light-gray"
                            >

                                <option value="">
                                    Select barangay
                                </option>

                            </select>

                            <p class="mt-1.5 text-xs text-charcoal/45">
                                Select a municipality first
                            </p>

                        </div>


                        <!-- HOUSE NUMBER -->

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
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                        </div>


                        <!-- STREET -->

                        <div class="md:col-span-2">

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
                                class="w-full rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                        </div>


                        <!-- ADDITIONAL ADDRESS -->

                        <div class="md:col-span-3">

                            <label
                                for="additional_address"
                                class="mb-2 block text-sm font-semibold text-charcoal"
                            >
                                Additional Address Information
                                <span class="text-charcoal/40">(Optional)</span>
                            </label>

                            <textarea
                                id="additional_address"
                                name="additional_address"
                                rows="3"
                                placeholder="Building, subdivision, landmark, floor, unit, etc."
                                class="w-full resize-none rounded-xl border border-light-gray bg-white px-4 py-3.5 text-sm outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >{{ old('additional_address') }}</textarea>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     IDENTITY VERIFICATION
                ================================================== -->

                <section class="border-b border-light-gray px-6 py-7 sm:px-8">

                    <div class="mb-6 flex items-start gap-4">

                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary"
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
                                    d="M15 7a3 3 0 11-6 0 3 3 0 016 0zM4 21a8 8 0 0116 0M19 4l1 1 2-2"
                                />

                            </svg>

                        </div>

                        <div>

                            <h3 class="text-lg font-bold text-charcoal">
                                Identity Verification
                            </h3>

                            <p class="mt-1 text-xs text-charcoal/55">
                                Upload a valid identification document for administrator verification.
                            </p>

                        </div>

                    </div>


                    <!-- UPLOAD AREA -->

                    <label
                        for="id_upload"
                        class="group flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-light-gray bg-light-gray/40 px-6 py-10 text-center transition hover:border-primary/40 hover:bg-ice-blue/50"
                    >

                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-primary shadow-sm transition group-hover:bg-primary group-hover:text-white"
                        >

                            <svg
                                class="h-7 w-7"
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


                        <p class="mt-4 text-sm font-bold text-charcoal">
                            Upload your valid ID
                        </p>


                        <p class="mt-1 max-w-md text-xs leading-5 text-charcoal/55">
                            Click here to select your identification document.
                            Make sure the image or document is clear and readable.
                        </p>


                        <p class="mt-3 text-xs font-medium text-primary">
                            JPG, JPEG, PNG or PDF
                        </p>


                        <input
                            type="file"
                            id="id_upload"
                            name="id_upload"
                            required
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="sr-only"
                        >

                    </label>


                    <!-- FILE NAME -->

                    <div
                        id="selectedFile"
                        class="mt-3 hidden rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3"
                    >

                        <div class="flex items-center gap-3">

                            <svg
                                class="h-5 w-5 shrink-0 text-leaf-green"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />

                            </svg>

                            <div class="min-w-0">

                                <p class="text-xs font-semibold text-leaf-green">
                                    Selected file
                                </p>

                                <p
                                    id="fileName"
                                    class="truncate text-xs text-charcoal/65"
                                ></p>

                            </div>

                        </div>

                    </div>


                    <p class="mt-3 text-xs leading-5 text-charcoal/50">
                        Your uploaded ID will be reviewed by the administrator
                        and will only be used for account verification.
                    </p>

                </section>


                <!-- =================================================
                     ADMIN APPROVAL NOTICE
                ================================================== -->

                <section class="px-6 py-7 sm:px-8">

                    <div
                        class="rounded-2xl border border-primary/10 bg-ice-blue p-5"
                    >

                        <div class="flex items-start gap-4">

                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-primary shadow-sm"
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
                                        d="M12 9v3m0 4h.01M10.3 3.8L2.9 17a2 2 0 001.75 3h14.7a2 2 0 001.75 3h14.7a2 2 0 001.75-3L13.7 3.8a2 2 0 00-3.4 0z"
                                    />

                                </svg>

                            </div>


                            <div>

                                <h3 class="text-sm font-bold text-charcoal">
                                    Administrator approval required
                                </h3>

                                <p class="mt-1 text-xs leading-5 text-charcoal/70 sm:text-sm">
                                    After submitting your registration, your
                                    application will be reviewed by a Shopleap
                                    administrator. You will receive an email
                                    notification once your registration has
                                    been approved or rejected.
                                </p>

                            </div>

                        </div>

                    </div>


                    <!-- TERMS -->

                    <div class="mt-6 flex items-start gap-3">

                        <input
                            type="checkbox"
                            id="terms"
                            name="terms"
                            required
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-light-gray accent-primary focus:ring-primary/20"
                        >

                        <label
                            for="terms"
                            class="text-xs leading-5 text-charcoal/65"
                        >
                            I confirm that the information I provided is
                            accurate and complete, and I agree to the
                            Shopleap terms and policies.
                            <span class="text-sale-red">*</span>
                        </label>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-4 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/20"
                    >

                        Submit Registration

                        <svg
                            class="h-5 w-5"
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


                    <!-- BACK -->

                    <div class="mt-5 text-center">

                        <a
                            href="{{ route('register') }}"
                            class="text-sm font-semibold text-charcoal/60 transition hover:text-primary"
                        >
                            ← Back to account type selection
                        </a>

                    </div>

                </section>

            </form>


            <!-- =================================================
                 LOGIN
            ================================================== -->

            <div class="mt-8 text-center">

                <p class="text-sm text-charcoal/65">

                    Already have a Shopleap account?

                    <a
                        href="{{ route('login') }}"
                        class="font-bold text-primary hover:text-sky-blue hover:underline"
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
         JAVASCRIPT
    ====================================================== -->

    <script>

        /*
        |--------------------------------------------------------------------------
        | Password Visibility
        |--------------------------------------------------------------------------
        */

        function togglePassword(inputId, iconId) {

            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {

                input.type = 'text';

                icon.innerHTML = `
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.7 10.7 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3 3.8M6.2 6.2C3.7 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.6-.3 3.7-.8"
                    />
                `;

            } else {

                input.type = 'password';

                icon.innerHTML = `
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"
                    />

                    <circle
                        cx="12"
                        cy="12"
                        r="2.5"
                        stroke-width="1.8"
                    />
                `;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Automatic Age Calculation
        |--------------------------------------------------------------------------
        */

        const birthdayInput = document.getElementById('birthday');
        const ageInput = document.getElementById('age');

        function calculateAge() {

            if (!birthdayInput.value) {

                ageInput.value = '';

                return;

            }

            const birthday = new Date(birthdayInput.value);
            const today = new Date();

            let age =
                today.getFullYear() -
                birthday.getFullYear();

            const monthDifference =
                today.getMonth() -
                birthday.getMonth();

            if (
                monthDifference < 0 ||
                (
                    monthDifference === 0 &&
                    today.getDate() < birthday.getDate()
                )
            ) {

                age--;

            }

            if (age >= 0) {

                ageInput.value = age;

            } else {

                ageInput.value = '';

            }

        }


        birthdayInput.addEventListener(
            'change',
            calculateAge
        );


        /*
        |--------------------------------------------------------------------------
        | ID File Selection
        |--------------------------------------------------------------------------
        */

        const idUpload =
            document.getElementById('id_upload');

        const selectedFile =
            document.getElementById('selectedFile');

        const fileName =
            document.getElementById('fileName');


        idUpload.addEventListener('change', function () {

            if (this.files && this.files.length > 0) {

                fileName.textContent =
                    this.files[0].name;

                selectedFile.classList.remove('hidden');

            } else {

                fileName.textContent = '';

                selectedFile.classList.add('hidden');

            }

        });


        /*
        |--------------------------------------------------------------------------
        | Middle Initial
        |--------------------------------------------------------------------------
        */

        const middleInitial =
            document.getElementById('middle_initial');

        middleInitial.addEventListener(
            'input',
            function () {

                this.value =
                    this.value
                        .replace(/[^a-zA-Z]/g, '')
                        .slice(0, 1)
                        .toUpperCase();

            }
        );

    </script>

</body>

</html>
