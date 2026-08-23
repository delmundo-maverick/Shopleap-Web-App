<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Shopleap | Shop Smart. Shop Simple.</title>
</head>

<body class="bg-white text-charcoal">

    <!-- =========================
         NAVIGATION BAR
    ========================== -->
    <header class="sticky top-0 z-50 border-b border-light-gray bg-white/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">

            <!-- Logo -->
            <a href="/" class="flex items-center gap-3">
                <!-- Logo Placeholder -->
                <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-ice-blue">
                    <!-- Replace this img src when you have your logo -->
                    <img
                        src=""
                        alt="Shopleap Logo"
                        class="h-full w-full object-contain"
                    >
                </div>

                <div>
                    <h1 class="text-xl font-bold tracking-tight text-primary">
                        Shopleap
                    </h1>
                    <p class="text-xs text-charcoal/70">
                        Shop Smart. Shop Simple.
                    </p>
                </div>
            </a>

            <!-- Desktop Navigation -->
            <div class="hidden items-center gap-8 md:flex">
                <a
                    href="#home"
                    class="text-sm font-medium text-charcoal transition hover:text-sky-blue"
                >
                    Home
                </a>

                <a
                    href="#categories"
                    class="text-sm font-medium text-charcoal transition hover:text-sky-blue"
                >
                    Categories
                </a>

                <a
                    href="#about"
                    class="text-sm font-medium text-charcoal transition hover:text-sky-blue"
                >
                    About
                </a>

                <a
                    href="#why-shopleap"
                    class="text-sm font-medium text-charcoal transition hover:text-sky-blue"
                >
                    Why Shopleap?
                </a>
            </div>

            <!-- Authentication Buttons -->
            <div class="hidden items-center gap-3 md:flex">
                <a
                    href="/login"
                    class="rounded-lg px-5 py-2.5 text-sm font-semibold text-primary transition hover:bg-ice-blue"
                >
                    Login
                </a>

                <a
                    href="/register"
                    class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-blue"
                >
                    Register
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <button
                id="mobile-menu-button"
                type="button"
                class="rounded-lg p-2 text-charcoal transition hover:bg-ice-blue md:hidden"
                aria-label="Open navigation menu"
                aria-expanded="false"
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
                        stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
            </button>
        </nav>

        <!-- Mobile Navigation -->
        <div
            id="mobile-menu"
            class="hidden border-t border-light-gray bg-white md:hidden"
        >
            <div class="space-y-1 px-6 py-4">

                <a
                    href="#home"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue hover:text-primary"
                >
                    Home
                </a>

                <a
                    href="#categories"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue hover:text-primary"
                >
                    Categories
                </a>

                <a
                    href="#about"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue hover:text-primary"
                >
                    About
                </a>

                <a
                    href="#why-shopleap"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue hover:text-primary"
                >
                    Why Shopleap?
                </a>

                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-light-gray pt-4">

                    <a
                        href="/login"
                        class="rounded-lg border border-primary px-4 py-3 text-center text-sm font-semibold text-primary transition hover:bg-ice-blue"
                    >
                        Login
                    </a>

                    <a
                        href="/register"
                        class="rounded-lg bg-primary px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-sky-blue"
                    >
                        Register
                    </a>

                </div>
            </div>
        </div>
    </header>


    <!-- =========================
         HERO SECTION
    ========================== -->
    <main>

        <section
            id="home"
            class="relative overflow-hidden bg-ice-blue"
        >
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:px-8 lg:py-28">

                <!-- Hero Content -->
                <div class="max-w-2xl">

                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/10 bg-white px-4 py-2">
                        <span class="h-2 w-2 rounded-full bg-leaf-green"></span>

                        <span class="text-sm font-medium text-charcoal">
                            Your trusted online marketplace
                        </span>
                    </div>

                    <h2 class="text-4xl font-extrabold leading-tight tracking-tight text-charcoal sm:text-5xl lg:text-6xl">
                        Shop Smart.
                        <span class="text-primary">Shop Simple.</span>
                    </h2>

                    <p class="mt-6 max-w-xl text-lg leading-8 text-charcoal/75">
                        Discover products from trusted sellers, enjoy convenient
                        shopping, and experience a marketplace designed to make
                        every purchase easier.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">

                        <a
                            href="/register"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue"
                        >
                            Start Shopping

                            <svg
                                class="ml-2 h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"
                                />
                            </svg>
                        </a>

                        <a
                            href="#categories"
                            class="inline-flex items-center justify-center rounded-xl border border-primary bg-white px-7 py-3.5 text-sm font-bold text-primary transition hover:bg-ice-blue"
                        >
                            Browse Categories
                        </a>

                    </div>

                    <!-- Small Stats -->
                    <div class="mt-10 grid max-w-lg grid-cols-3 gap-6 border-t border-primary/10 pt-8">

                        <div>
                            <p class="text-2xl font-bold text-primary">100%</p>
                            <p class="mt-1 text-xs text-charcoal/65">
                                Convenient
                            </p>
                        </div>

                        <div>
                            <p class="text-2xl font-bold text-primary">24/7</p>
                            <p class="mt-1 text-xs text-charcoal/65">
                                Marketplace
                            </p>
                        </div>

                        <div>
                            <p class="text-2xl font-bold text-leaf-green">Secure</p>
                            <p class="mt-1 text-xs text-charcoal/65">
                                Shopping
                            </p>
                        </div>

                    </div>

                </div>


                <!-- Hero Image Placeholder -->
                <div class="relative">

                    <div class="relative mx-auto aspect-square max-w-lg overflow-hidden rounded-3xl border border-primary/10 bg-white shadow-xl">

                        <!-- Replace this with your hero image later -->
                        <img
                            src=""
                            alt="Shopleap Marketplace"
                            class="h-full w-full object-cover"
                        >

                        <!-- Temporary placeholder -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 text-center backdrop-blur-sm">

                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-ice-blue text-primary">

                                <svg
                                    class="h-8 w-8"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M3 7h18M5 7v12h14V7M8 7V5a4 4 0 018 0v2"
                                    />
                                </svg>

                            </div>

                            <p class="font-semibold text-charcoal">
                                Shopleap
                            </p>

                            <p class="mt-1 text-sm text-charcoal/60">
                                Hero image placeholder
                            </p>

                        </div>

                    </div>

                    <!-- Decorative Element -->
                    <div class="absolute -bottom-5 -left-5 -z-0 h-24 w-24 rounded-3xl bg-leaf-green/20"></div>

                    <div class="absolute -right-5 -top-5 -z-0 h-28 w-28 rounded-full bg-sky-blue/20"></div>

                </div>

            </div>
        </section>


        <!-- =========================
             CATEGORIES SECTION
        ========================== -->
        <section
            id="categories"
            class="bg-white py-20"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-8">

                <div class="mx-auto max-w-2xl text-center">

                    <span class="text-sm font-bold uppercase tracking-wider text-primary">
                        Explore
                    </span>

                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-charcoal sm:text-4xl">
                        Browse Categories
                    </h2>

                    <p class="mt-4 text-charcoal/70">
                        Find what you need from a variety of product categories
                        available on Shopleap.
                    </p>

                </div>


                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">

                    <!-- Category 1 -->
                    <div class="group rounded-2xl border border-light-gray bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-sky-blue hover:shadow-md">

                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-ice-blue text-primary transition group-hover:bg-primary group-hover:text-white">

                            <svg
                                class="h-8 w-8"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M3 7h18M5 7v12h14V7M8 7V5a4 4 0 018 0v2"
                                />
                            </svg>

                        </div>

                        <h3 class="mt-5 font-bold text-charcoal">
                            Fashion
                        </h3>

                        <p class="mt-2 text-sm text-charcoal/60">
                            Clothing and accessories
                        </p>

                    </div>


                    <!-- Category 2 -->
                    <div class="group rounded-2xl border border-light-gray bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-sky-blue hover:shadow-md">

                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-ice-blue text-primary transition group-hover:bg-primary group-hover:text-white">

                            <svg
                                class="h-8 w-8"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M9 3h6v4H9zM5 7h14v14H5zM9 11h6M9 15h6"
                                />
                            </svg>

                        </div>

                        <h3 class="mt-5 font-bold text-charcoal">
                            Electronics
                        </h3>

                        <p class="mt-2 text-sm text-charcoal/60">
                            Gadgets and technology
                        </p>

                    </div>


                    <!-- Category 3 -->
                    <div class="group rounded-2xl border border-light-gray bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-sky-blue hover:shadow-md">

                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-ice-blue text-primary transition group-hover:bg-primary group-hover:text-white">

                            <svg
                                class="h-8 w-8"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4 6h16M4 10h16M4 14h10M4 18h7"
                                />
                            </svg>

                        </div>

                        <h3 class="mt-5 font-bold text-charcoal">
                            Home & Living
                        </h3>

                        <p class="mt-2 text-sm text-charcoal/60">
                            Essentials for your home
                        </p>

                    </div>


                    <!-- Category 4 -->
                    <div class="group rounded-2xl border border-light-gray bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-sky-blue hover:shadow-md">

                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-ice-blue text-primary transition group-hover:bg-primary group-hover:text-white">

                            <svg
                                class="h-8 w-8"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3z"
                                />
                            </svg>

                        </div>

                        <h3 class="mt-5 font-bold text-charcoal">
                            More
                        </h3>

                        <p class="mt-2 text-sm text-charcoal/60">
                            Discover more products
                        </p>

                    </div>

                </div>

            </div>
        </section>


        <!-- =========================
             ABOUT SECTION
        ========================== -->
        <section
            id="about"
            class="bg-ice-blue py-20"
        >
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-2 lg:px-8">

                <!-- Image Placeholder -->
                <div class="order-2 lg:order-1">

                    <div class="aspect-[4/3] overflow-hidden rounded-3xl border border-primary/10 bg-white shadow-lg">

                        <!-- Replace this with an actual image later -->
                        <img
                            src=""
                            alt="About Shopleap"
                            class="h-full w-full object-cover"
                        >

                        <div class="flex h-full min-h-[320px] items-center justify-center bg-white">

                            <div class="text-center">

                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-ice-blue text-primary">

                                    <svg
                                        class="h-8 w-8"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M3 7h18M5 7v12h14V7M8 7V5a4 4 0 018 0v2"
                                        />
                                    </svg>

                                </div>

                                <p class="mt-4 font-semibold text-charcoal">
                                    Shopleap
                                </p>

                                <p class="mt-1 text-sm text-charcoal/60">
                                    Image placeholder
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Content -->
                <div class="order-1 lg:order-2">

                    <span class="text-sm font-bold uppercase tracking-wider text-primary">
                        About Shopleap
                    </span>

                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-charcoal sm:text-4xl">
                        A simpler way to shop online.
                    </h2>

                    <p class="mt-6 leading-7 text-charcoal/70">
                        Shopleap is an online marketplace designed to connect
                        customers with trusted sellers while making the shopping
                        experience simple, convenient, and accessible.
                    </p>

                    <p class="mt-4 leading-7 text-charcoal/70">
                        Whether you're looking for everyday essentials, the latest
                        gadgets, or something special, Shopleap brings products
                        together in one convenient place.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">

                        <div class="flex gap-3">

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-leaf-green/15 text-leaf-green">
                                ✓
                            </div>

                            <div>
                                <h3 class="font-semibold text-charcoal">
                                    Trusted Marketplace
                                </h3>

                                <p class="mt-1 text-sm text-charcoal/60">
                                    Built with users and sellers in mind.
                                </p>
                            </div>

                        </div>


                        <div class="flex gap-3">

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-leaf-green/15 text-leaf-green">
                                ✓
                            </div>

                            <div>
                                <h3 class="font-semibold text-charcoal">
                                    Easy Shopping
                                </h3>

                                <p class="mt-1 text-sm text-charcoal/60">
                                    Simple browsing and ordering experience.
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </section>


        <!-- =========================
             WHY SHOPLEAP
        ========================== -->
        <section
            id="why-shopleap"
            class="bg-white py-20"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-8">

                <div class="mx-auto max-w-2xl text-center">

                    <span class="text-sm font-bold uppercase tracking-wider text-primary">
                        Why Shopleap?
                    </span>

                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-charcoal sm:text-4xl">
                        Shopping made easier
                    </h2>

                    <p class="mt-4 text-charcoal/70">
                        Everything you need for a convenient online shopping
                        experience.
                    </p>

                </div>


                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">

                    <div class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-ice-blue text-primary">
                            ✓
                        </div>

                        <h3 class="mt-5 font-bold text-charcoal">
                            Trusted Sellers
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-charcoal/65">
                            Connect with registered sellers and discover products
                            from different categories.
                        </p>
                    </div>


                    <div class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-ice-blue text-primary">
                            ✓
                        </div>

                        <h3 class="mt-5 font-bold text-charcoal">
                            Secure Shopping
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-charcoal/65">
                            Designed with account security and safe transactions
                            in mind.
                        </p>
                    </div>


                    <div class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-ice-blue text-primary">
                            ✓
                        </div>

                        <h3 class="mt-5 font-bold text-charcoal">
                            Convenient Delivery
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-charcoal/65">
                            A connected seller and courier system helps orders move
                            toward delivery.
                        </p>
                    </div>


                    <div class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-ice-blue text-primary">
                            ✓
                        </div>

                        <h3 class="mt-5 font-bold text-charcoal">
                            Simple Experience
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-charcoal/65">
                            A clean interface designed to make browsing and
                            shopping straightforward.
                        </p>
                    </div>

                </div>

            </div>
        </section>


        <!-- =========================
             CALL TO ACTION
        ========================== -->
        <section class="bg-primary">
            <div class="mx-auto max-w-7xl px-6 py-16 text-center lg:px-8">

                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Ready to start shopping?
                </h2>

                <p class="mx-auto mt-4 max-w-2xl text-white/80">
                    Join Shopleap and discover a simpler way to shop online.
                </p>

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">

                    <a
                        href="/register"
                        class="rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-primary transition hover:bg-ice-blue"
                    >
                        Create an Account
                    </a>

                    <a
                        href="/login"
                        class="rounded-xl border border-white/40 px-7 py-3.5 text-sm font-bold text-white transition hover:bg-white/10"
                    >
                        Login
                    </a>

                </div>

            </div>
        </section>

    </main>


    <!-- =========================
         FOOTER
    ========================== -->
    <footer class="bg-charcoal text-white">

        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">

                <!-- Brand -->
                <div>

                    <div class="flex items-center gap-3">

                        <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-white">
                            <!-- Logo placeholder -->
                            <img
                                src=""
                                alt="Shopleap Logo"
                                class="h-full w-full object-contain"
                            >
                        </div>

                        <span class="text-xl font-bold">
                            Shopleap
                        </span>

                    </div>

                    <p class="mt-4 max-w-sm text-sm leading-6 text-white/65">
                        A convenient online marketplace designed to make shopping
                        simple and accessible.
                    </p>

                </div>


                <!-- Marketplace -->
                <div>

                    <h3 class="font-semibold">
                        Marketplace
                    </h3>

                    <ul class="mt-4 space-y-3 text-sm text-white/65">

                        <li>
                            <a href="#categories" class="transition hover:text-white">
                                Categories
                            </a>
                        </li>

                        <li>
                            <a href="#about" class="transition hover:text-white">
                                About Shopleap
                            </a>
                        </li>

                        <li>
                            <a href="#why-shopleap" class="transition hover:text-white">
                                Why Shopleap?
                            </a>
                        </li>

                    </ul>

                </div>


                <!-- Account -->
                <div>

                    <h3 class="font-semibold">
                        Account
                    </h3>

                    <ul class="mt-4 space-y-3 text-sm text-white/65">

                        <li>
                            <a href="/login" class="transition hover:text-white">
                                Login
                            </a>
                        </li>

                        <li>
                            <a href="/register" class="transition hover:text-white">
                                Register
                            </a>
                        </li>

                    </ul>

                </div>


                <!-- Become a Seller -->
                <div>

                    <h3 class="font-semibold">
                        Start Selling
                    </h3>

                    <p class="mt-4 text-sm leading-6 text-white/65">
                        Want to sell your products on Shopleap?
                        Register as a seller and grow your business.
                    </p>

                    <a
                        href="/register"
                        class="mt-5 inline-flex rounded-lg bg-leaf-green px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        Become a Seller
                    </a>

                </div>

            </div>


            <!-- Bottom -->
            <div class="mt-12 border-t border-white/10 pt-6">

                <div class="flex flex-col gap-3 text-sm text-white/50 sm:flex-row sm:items-center sm:justify-between">

                    <p>
                        © {{ date('Y') }} Shopleap. All rights reserved.
                    </p>

                    <p>
                        Shop Smart. Shop Simple.
                    </p>

                </div>

            </div>

        </div>

    </footer>


    <!-- =========================
         MOBILE MENU SCRIPT
    ========================== -->
    <script>
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.contains('hidden');

            mobileMenu.classList.toggle('hidden');

            mobileMenuButton.setAttribute(
                'aria-expanded',
                isHidden ? 'true' : 'false'
            );
        });

        // Close mobile menu when a navigation link is clicked
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                mobileMenuButton.setAttribute('aria-expanded', 'false');
            });
        });
    </script>

</body>
</html>
