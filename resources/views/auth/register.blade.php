<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Register | Shopleap</title>
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

                <a href="{{ route('login') }}" class="font-semibold text-primary transition hover:text-sky-blue">
                    Log in
                </a>

            </div>

        </div>

    </header>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="px-4 py-12 sm:px-6 lg:py-16">

        <div class="mx-auto max-w-5xl">


            <!-- =================================================
                 PAGE INTRODUCTION
            ================================================== -->

            <div class="mx-auto max-w-2xl text-center">

                <!-- Icon -->

                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/20">

                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5.5 21a6.5 6.5 0 0113 0M19 8v6m3-3h-6" />

                    </svg>

                </div>


                <h2 class="mt-6 text-3xl font-bold tracking-tight text-charcoal sm:text-4xl">
                    Create your Shopleap account
                </h2>


                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-charcoal/70 sm:text-base">
                    Choose the account type you want to register.
                    Every registration is reviewed by a Shopleap administrator.
                </p>

            </div>


            <!-- =================================================
                 ACCOUNT TYPE CARDS
            ================================================== -->

            <div class="mt-10 grid gap-6 md:grid-cols-3">


                <!-- =================================================
                     BUYER
                ================================================== -->

                <div
                    class="group flex flex-col rounded-2xl border border-light-gray bg-white p-7 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl">

                    <!-- ICON -->

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-ice-blue text-primary transition group-hover:bg-primary group-hover:text-white">

                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 3h11.8M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />

                        </svg>

                    </div>


                    <!-- CONTENT -->

                    <div class="mt-6">

                        <div class="flex items-center justify-between gap-3">

                            <h3 class="text-xl font-bold text-charcoal">
                                Buyer
                            </h3>

                            <span
                                class="rounded-full bg-ice-blue px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-primary">
                                Shop
                            </span>

                        </div>


                        <p class="mt-3 text-sm leading-6 text-charcoal/70">
                            Create an account to browse products, place
                            orders, track deliveries, and interact with sellers.
                        </p>

                    </div>


                    <!-- FEATURES -->

                    <ul class="mt-6 space-y-3">

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Browse and purchase products

                        </li>

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Track orders

                        </li>

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Rate and provide feedback

                        </li>

                    </ul>


                    <!-- BUTTON -->

                    <div class="mt-auto pt-8">

                        <a href="{{ route('register.buyer') }}"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-sm font-bold text-white transition hover:bg-sky-blue focus:outline-none focus:ring-4 focus:ring-primary/20">

                            Register as Buyer

                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 12h14m-6-6l6 6-6 6" />

                            </svg>

                        </a>

                    </div>

                </div>


                <!-- =================================================
                     SELLER
                ================================================== -->

                <div
                    class="group flex flex-col rounded-2xl border border-light-gray bg-white p-7 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl">

                    <!-- ICON -->

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-ice-blue text-primary transition group-hover:bg-primary group-hover:text-white">

                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 10l2-5h14l2 5M4 10h16v10H4V10zm4 4h8" />

                        </svg>

                    </div>


                    <!-- CONTENT -->

                    <div class="mt-6">

                        <div class="flex items-center justify-between gap-3">

                            <h3 class="text-xl font-bold text-charcoal">
                                Seller
                            </h3>

                            <span
                                class="rounded-full bg-ice-blue px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-primary">
                                Sell
                            </span>

                        </div>


                        <p class="mt-3 text-sm leading-6 text-charcoal/70">
                            Register your business, list products, manage
                            inventory, process orders, and monitor sales.
                        </p>

                    </div>


                    <!-- FEATURES -->

                    <ul class="mt-6 space-y-3">

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Manage products and inventory

                        </li>

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Manage customer orders

                        </li>

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Generate sales reports

                        </li>

                    </ul>


                    <!-- BUTTON -->

                    <div class="mt-auto pt-8">

                        <a href="{{ route('register.seller') }}"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-primary bg-white px-5 py-3 text-sm font-bold text-primary transition hover:bg-primary hover:text-white focus:outline-none focus:ring-4 focus:ring-primary/20">

                            Register as Seller

                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 12h14m-6-6l6 6-6 6" />

                            </svg>

                        </a>

                    </div>

                </div>


                <!-- =================================================
                     LOGISTICS
                ================================================== -->

                <div
                    class="group flex flex-col rounded-2xl border border-light-gray bg-white p-7 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-leaf-green/40 hover:shadow-xl">

                    <!-- ICON -->

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-leaf-green/10 text-leaf-green transition group-hover:bg-leaf-green group-hover:text-white">

                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 7h11v10H3V7zm11 3h4l3 3v4h-7v-7zm-7 9a2 2 0 100-4 2 2 0 000 4zm9 0a2 2 0 100-4 2 2 0 000 4z" />

                        </svg>

                    </div>


                    <!-- CONTENT -->

                    <div class="mt-6">

                        <div class="flex items-center justify-between gap-3">

                            <h3 class="text-xl font-bold text-charcoal">
                                Logistics
                            </h3>

                            <span
                                class="rounded-full bg-leaf-green/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-leaf-green">
                                Deliver
                            </span>

                        </div>


                        <p class="mt-3 text-sm leading-6 text-charcoal/70">
                            Register your logistics company to handle
                            pickups, deliveries, shipments, and delivery
                            requests.
                        </p>

                    </div>


                    <!-- FEATURES -->

                    <ul class="mt-6 space-y-3">

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Accept delivery requests

                        </li>

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Pick up and deliver orders

                        </li>

                        <li class="flex items-center gap-3 text-sm">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                                ✓
                            </span>

                            Track delivery history

                        </li>

                    </ul>


                    <!-- BUTTON -->

                    <div class="mt-auto pt-8">

                        <a href="{{ route('register.logistics') }}"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-leaf-green bg-white px-5 py-3 text-sm font-bold text-leaf-green transition hover:bg-leaf-green hover:text-white focus:outline-none focus:ring-4 focus:ring-leaf-green/20">

                            Register as Logistics

                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 12h14m-6-6l6 6-6 6" />

                            </svg>

                        </a>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 ADMIN APPROVAL NOTICE
            ================================================== -->

            <div
                class="mx-auto mt-8 flex max-w-3xl items-start gap-4 rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">

                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M12 9v3m0 4h.01M10.3 3.8L2.9 17a2 2 0 001.75 3h14.7a2 2 0 001.75-3L13.7 3.8a2 2 0 00-3.4 0z" />

                    </svg>

                </div>


                <div>

                    <h3 class="text-sm font-bold text-charcoal">
                        Administrator approval required
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-charcoal/70 sm:text-sm">
                        All Buyer, Seller, and Logistics registrations are
                        reviewed by a Shopleap administrator. You will receive
                        an email notification once your application has been
                        approved or rejected.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 LOGIN REMINDER
            ================================================== -->

            <div class="mt-8 text-center">

                <p class="text-sm text-charcoal/70">

                    Already registered?

                    <a href="{{ route('login') }}" class="font-bold text-primary hover:text-sky-blue hover:underline">
                        Sign in to your account
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

</body>

</html>
