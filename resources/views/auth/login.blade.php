<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/auth/login.js'])

    <title>Login | Shopleap</title>
</head>

<body class="h-screen h-dvh overflow-hidden bg-ice-blue text-charcoal">

    @php
        // Shared input styling; the border turns red when the field has a validation error.
        $field = fn(string $name, string $padding = 'pr-4') => implode(' ', [
            'w-full rounded-xl border bg-white py-3 pl-11 text-sm text-charcoal outline-none transition',
            'placeholder:text-charcoal/35 focus:ring-4',
            $padding,
            $errors->has($name)
                ? 'border-sale-red/60 focus:border-sale-red focus:ring-sale-red/10'
                : 'border-light-gray focus:border-primary focus:ring-primary/10',
        ]);

        $iconClass = 'h-5 w-5 text-charcoal/40';

        $forgotPasswordUrl = \Illuminate\Support\Facades\Route::has('password.request')
            ? route('password.request')
            : '#';

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

    <div class="h-full lg:grid lg:grid-cols-2">

        {{-- ================= BRAND PANEL (desktop only) ================= --}}
        <aside
            class="relative hidden h-full overflow-hidden bg-gradient-to-br from-primary to-sky-blue p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-12">

            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-white/10"></div>

            <x-logo />

            <div class="relative max-w-md">
                <h2 class="text-3xl font-bold leading-tight xl:text-4xl">Shop Smart.<br>Shop Simple.</h2>
                <p class="mt-3 text-sm leading-6 text-white/80">
                    Welcome back! Log in to pick up right where you left off.
                </p>

                <ul class="mt-8 space-y-4 xl:mt-10 xl:space-y-5">
                    @foreach ($highlights as $item)
                        <li class="flex items-start gap-4">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 backdrop-blur">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="{{ $item['icon'] }}" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold">{{ $item['title'] }}</p>
                                <p class="mt-0.5 text-xs leading-5 text-white/70 [@media(max-height:760px)]:hidden">
                                    {{ $item['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-white/60">© {{ date('Y') }} Shopleap. All rights reserved.</p>
        </aside>

        {{-- ================= FORM PANEL ================= --}}
        <div class="flex h-full min-h-0 flex-col">

            <header class="flex h-16 shrink-0 items-center justify-between px-4 sm:h-20 sm:px-8 lg:justify-end">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 lg:hidden">
                    <span
                        class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl bg-white p-1 shadow-sm sm:h-10 sm:w-10">
                        <img src="{{ asset('images/Shopleap-Logo.png') }}" alt="Shopleap"
                            class="h-full w-full object-contain">
                    </span>
                    <span>
                        <span class="block text-base font-bold leading-tight text-primary sm:text-lg">Shopleap</span>
                        <span class="hidden text-xs text-charcoal/60 sm:block">Shop Smart. Shop Simple.</span>
                    </span>
                </a>

                <div class="flex items-center gap-2 text-sm">
                    <span class="hidden text-charcoal/60 sm:inline">New to Shopleap?</span>
                    <a href="{{ route('register') }}"
                        class="rounded-lg bg-white px-3 py-1.5 font-semibold text-primary shadow-sm transition hover:text-sky-blue sm:px-3.5 sm:py-2">
                        Create an account
                    </a>
                </div>
            </header>

            {{-- m-auto on the child keeps it centered; the container only scrolls if the screen is too short to fit it --}}
            <main class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 py-2 sm:px-6">
                <div class="m-auto w-full max-w-md">

                    <div class="rounded-2xl border border-light-gray bg-white p-5 shadow-xl shadow-charcoal/5 sm:p-8">

                        <div class="text-center lg:text-left">
                            <h1 class="text-2xl font-bold text-charcoal sm:text-3xl">Log In</h1>
                            <p class="mt-1.5 text-sm leading-6 text-charcoal/60">
                                Log in to your Shopleap account to continue.
                            </p>
                        </div>

                        <form id="loginForm" method="POST" action="{{ route('login') }}" class="mt-5 sm:mt-7">
                            @csrf

                            @if (session('status'))
                                <div
                                    class="mb-4 rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-2.5 text-sm text-leaf-green">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div role="alert"
                                    class="mb-4 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-2.5">
                                    <p class="text-sm font-semibold text-sale-red">
                                        Please check your login information.
                                    </p>
                                    <ul class="mt-1 list-inside list-disc text-xs text-sale-red/80">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- E-mail --}}
                            <div>
                                <label for="email" class="mb-1.5 block text-sm font-semibold text-charcoal">
                                    E-mail
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <svg class="{{ $iconClass }}" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                        required autofocus autocomplete="email" placeholder="Enter your e-mail"
                                        class="{{ $field('email') }}">
                                </div>
                            </div>

                            {{-- Password --}}
                            <div class="mt-4">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label for="password" class="block text-sm font-semibold text-charcoal">
                                        Password
                                    </label>
                                    <a href="{{ $forgotPasswordUrl }}"
                                        class="text-xs font-semibold text-primary transition hover:text-sky-blue">
                                        Forgot password?
                                    </a>
                                </div>

                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <svg class="{{ $iconClass }}" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M7 10V7a5 5 0 0110 0v3M6 10h12a2 2 0 012 2v7a2 2 0 01-2 2H6a2 2 0 01-2-2v-7a2 2 0 012-2z" />
                                        </svg>
                                    </span>

                                    <input type="password" id="password" name="password" required
                                        autocomplete="current-password" placeholder="Enter your password"
                                        class="{{ $field('password', 'pr-12') }}">

                                    <button type="button" id="togglePassword"
                                        class="absolute inset-y-0 right-0 flex items-center px-4 text-charcoal/40 transition hover:text-primary"
                                        aria-label="Show password">
                                        <svg id="eyeOpen" class="h-5 w-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />
                                            <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                                        </svg>
                                        <svg id="eyeClosed" class="hidden h-5 w-5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.7 10.7 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3 3.8M6.2 6.2C3.7 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.6-.3 3.7-.8" />
                                        </svg>
                                    </button>
                                </div>

                                <p id="capsWarning" class="mt-1.5 hidden text-xs font-semibold text-sale-red">
                                    Caps Lock is on.
                                </p>
                            </div>

                            {{-- Remember me --}}
                            <label class="mt-4 flex cursor-pointer items-center gap-2.5">
                                <input type="checkbox" name="remember"
                                    class="h-4 w-4 rounded border-light-gray text-primary accent-primary focus:ring-primary/20">
                                <span class="text-xs text-charcoal/65 sm:text-sm">Remember me</span>
                            </label>

                            {{-- Submit --}}
                            <button type="submit" id="loginSubmit"
                                class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-70">
                                <span id="loginSubmitLabel">Log In</span>
                                <svg id="loginSubmitArrow" class="h-4 w-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 12h14m-6-6l6 6-6 6" />
                                </svg>
                            </button>

                            <p class="mt-5 text-center text-sm text-charcoal/65">
                                Don't have an account?
                                <a href="{{ route('register') }}"
                                    class="font-bold text-primary transition hover:text-sky-blue hover:underline">
                                    Create an account
                                </a>
                            </p>
                        </form>
                    </div>

                    <div
                        class="mt-4 hidden items-start justify-center gap-2 text-center sm:flex lg:hidden [@media(max-height:760px)]:hidden">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-leaf-green" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4z" />
                        </svg>
                        <p class="max-w-sm text-xs leading-5 text-charcoal/55">
                            Your account information is securely handled by Shopleap.
                        </p>
                    </div>

                </div>
            </main>

            <footer class="shrink-0 px-5 py-3 text-center text-xs text-charcoal/55 lg:hidden">
                © {{ date('Y') }} Shopleap. All rights reserved.
            </footer>
        </div>
    </div>

</body>

</html>
