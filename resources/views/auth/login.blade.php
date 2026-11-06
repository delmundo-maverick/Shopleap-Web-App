<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/auth/login.js'])

    <title>Login | Shopleap</title>
</head>

<body class="min-h-screen min-h-dvh bg-ice-blue text-charcoal antialiased">

    @php
        $field = fn(string $name, string $padding = 'pr-4') => implode(' ', [
            'frost-input w-full rounded-xl py-3.5 pl-12 text-sm outline-none transition sm:py-4',
            $padding,
            $errors->has($name) ? 'border-sale-red/60' : '',
        ]);

        $iconClass = 'h-5 w-5 text-charcoal/45';

        $highlights = [
            [
                'title' => 'Track every order',
                'text' => 'Follow your purchases from checkout to your doorstep.',
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            ],
            [
                'title' => 'Run your shop',
                'text' => 'Manage products, orders and deliveries in one place.',
                'icon' => 'M3 9l1-5h16l1 5M3 9h18M3 9v10a1 1 0 001 1h16a1 1 0 001-1V9M9 20v-6h6v6',
            ],
            [
                'title' => 'Secure by design',
                'text' => 'Your account information is securely handled by Shopleap.',
                'icon' => 'M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4z',
            ],
        ];
    @endphp

    <div class="grid min-h-screen min-h-dvh grid-cols-1 lg:grid-cols-[0.8fr_1.2fr]">

        {{-- BRAND PANEL: supporting content on desktop --}}
        <aside
            class="relative hidden overflow-hidden bg-gradient-to-br from-primary to-sky-blue px-8 py-10 text-white lg:flex lg:min-h-screen lg:flex-col lg:justify-between xl:px-12 xl:py-12">

            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-white/10"></div>

            {{-- Desktop logo: same styling as mobile --}}
            <a href="{{ route('home') }}" class="relative flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden">
                    <img src="{{ asset('images/Shopleap-Logo-White.png') }}" alt="Shopleap"
                        class="h-full w-full object-contain">
                </span>
                <span class="font-logo text-[1.65rem] font-medium leading-none tracking-tight">Shopleap</span>
            </a>

            <div class="relative mx-auto w-full max-w-md">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/90">
                    <span class="h-2 w-2 rounded-full bg-leaf-green"></span>
                    YOUR SHOPPING COMPANION
                </span>

                <h2 class="mt-6 text-3xl font-bold leading-tight xl:text-5xl">
                    Shop Smart.<br>
                    Shop Simple.
                </h2>

                <p class="mt-4 max-w-sm text-sm leading-7 text-white/80 xl:text-base">
                    Welcome back! Sign in to manage your account, track purchases, and keep your shop moving.
                </p>

                <ul class="mt-8 space-y-5 xl:mt-10">
                    @foreach ($highlights as $item)
                        <li class="flex items-start gap-4">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/15 bg-white/10">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="{{ $item['icon'] }}" />
                                </svg>
                            </span>

                            <div class="pt-0.5">
                                <p class="text-sm font-semibold">{{ $item['title'] }}</p>
                                <p class="mt-1 text-xs leading-5 text-white/70">{{ $item['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-white/60">
                © {{ date('Y') }} Shopleap. All rights reserved.
            </p>
        </aside>

        {{-- LOGIN PANEL: primary focus --}}
        <section class="relative flex min-w-0 flex-col bg-ice-blue">

        {{-- Mobile Header --}}
            <x-nav.guest class="lg:hidden" />

            {{-- Centered content; page scrolls naturally on short screens --}}
            <main class="flex flex-1 flex-col justify-center px-4 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-8 xl:px-16">
                <div class="mx-auto w-full max-w-xl">
                    {{-- Login card --}}
                    <div class="frost-panel-solid rounded-2xl p-5 sm:rounded-3xl sm:p-8 lg:p-9">

                        <div class="mb-6 border-b border-light-gray pb-5 sm:mb-7 sm:pb-6">
                            <h2 class="text-lg font-bold text-charcoal sm:text-xl">Sign in to your account</h2>
                            <p class="mt-1.5 text-sm leading-6 text-charcoal/60">
                                Enter your credentials below to continue.
                            </p>
                        </div>

                        <form id="loginForm" method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            @if (session('status'))
                                <div role="status"
                                    class="rounded-xl border border-leaf-green/25 bg-leaf-green/10 px-4 py-3 text-sm text-charcoal">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div role="alert"
                                    class="rounded-xl border border-sale-red/25 bg-sale-red/10 px-4 py-3">
                                    <p class="text-sm font-semibold text-sale-red">
                                        Please check your login information.
                                    </p>
                                    <ul class="mt-1 list-inside list-disc space-y-1 text-xs leading-5 text-charcoal/75">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Email --}}
                            <div>
                                <label for="email" class="mb-2 block text-sm font-semibold text-charcoal">
                                    E-mail address
                                </label>

                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <svg class="{{ $iconClass }}" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </span>

                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                        required autofocus autocomplete="email" placeholder="Enter your e-mail"
                                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                        class="{{ $field('email') }}">
                                </div>

                                @error('email')
                                    <p class="mt-1.5 text-xs font-medium text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div>
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <label for="password" class="text-sm font-semibold text-charcoal">
                                        Password
                                    </label>


                                </div>

                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <svg class="{{ $iconClass }}" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M7 10V7a5 5 0 0110 0v3M6 10h12a2 2 0 012 2v7a2 2 0 01-2 2H6a2 2 0 01-2-2v-7a2 2 0 012-2z" />
                                        </svg>
                                    </span>

                                    <input type="password" id="password" name="password" required
                                        autocomplete="current-password" placeholder="Enter your password"
                                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                        class="{{ $field('password', 'pr-12') }}">

                                    <button type="button" id="togglePassword"
                                        class="absolute inset-y-0 right-0 flex items-center px-4 text-charcoal/45 transition hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                        aria-label="Show password" aria-controls="password">
                                        <svg id="eyeOpen" class="h-5 w-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />
                                            <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                                        </svg>

                                        <svg id="eyeClosed" class="hidden h-5 w-5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.7 10.7 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3 3.8M6.2 6.2C3.7 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.6-.3 3.7-.8" />
                                        </svg>
                                    </button>
                                </div>

                                <a href="{{ route('password.request') }}"
                                    class="mt-2 inline-block text-xs font-bold text-primary transition hover:text-sky-blue hover:underline sm:text-sm">
                                    Forgot password?
                                </a>
                                @error('password')
                                    <p class="mt-1.5 text-xs font-medium text-sale-red">{{ $message }}</p>
                                @enderror

                                <p id="capsWarning" class="mt-1.5 hidden text-xs font-semibold text-sale-red">
                                    Caps Lock is on.
                                </p>
                            </div>

                            {{-- Remember me --}}
                            <label class="flex cursor-pointer items-center gap-2.5 py-1">
                                <input type="checkbox" name="remember" @checked(old('remember'))
                                    class="h-4 w-4 rounded border-light-gray accent-primary focus:ring-2 focus:ring-primary/20">
                                <span class="text-sm text-charcoal/70">Remember me</span>
                            </label>

                            {{-- Submit --}}
                            <button type="submit" id="loginSubmit"
                                class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition duration-200 hover:bg-sky-blue hover:shadow-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-70 sm:py-4 sm:text-base">
                                <span id="loginSubmitLabel">Log In</span>

                                <svg id="loginSubmitArrow" class="h-5 w-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 12h14m-6-6l6 6-6 6" />
                                </svg>
                            </button>

                            <p class="pt-1 text-center text-sm leading-6 text-charcoal/65">
                                Don't have an account?
                                <a href="{{ route('register') }}"
                                    class="font-bold text-primary transition hover:text-sky-blue hover:underline">
                                    Create an account
                                </a>
                            </p>
                        </form>
                    </div>

                    {{-- Security note --}}
                    <div class="mt-6 flex items-start justify-center gap-2.5 px-2 text-center">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-leaf-green" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4z" />
                        </svg>

                        <p class="max-w-md text-xs leading-5 text-charcoal/55">
                            Your account information is securely handled by Shopleap.
                        </p>
                    </div>
                </div>
            </main>
        </section>
    </div>

</body>

</html>
