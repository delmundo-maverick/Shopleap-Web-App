<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Login | Shopleap</title>
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


                <!-- BRAND NAME -->

                <div>

                    <h1 class="text-lg font-bold leading-tight text-primary">
                        Shopleap
                    </h1>

                    <p class="text-xs text-charcoal/60">
                        Shop Smart. Shop Simple.
                    </p>

                </div>

            </a>


            <!-- REGISTER -->

            <div class="flex items-center gap-2 text-sm">

                <span class="hidden text-charcoal/60 sm:inline">
                    New to Shopleap?
                </span>

                <a href="{{ route('register') }}" class="font-semibold text-primary transition hover:text-sky-blue">
                    Create an account
                </a>

            </div>

        </div>

    </header>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="flex min-h-[calc(100vh-9rem)] items-center px-4 py-12 sm:px-6">

        <div class="mx-auto w-full max-w-md">


            <!-- =================================================
                 LOGIN CARD
            ================================================== -->

            <div class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-xl shadow-charcoal/5">


                <!-- CARD HEADER -->

                <div class="px-6 pb-2 pt-8 text-center sm:px-8">

                    <!-- LOGIN ICON -->

                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/20">

                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5m5 5H3" />

                        </svg>

                    </div>


                    <h2 class="mt-5 text-2xl font-bold text-charcoal sm:text-3xl">
                        Log In
                    </h2>


                    <p class="mt-2 text-sm leading-6 text-charcoal/60">
                        Log in to your Shopleap account to continue.
                    </p>

                </div>


                <!-- =================================================
                     FORM
                ================================================== -->

                <form method="POST" action="{{ route('login') }}" class="px-6 pb-8 pt-7 sm:px-8">

                    @csrf


                    <!-- SESSION STATUS -->

                    @if (session('status'))
                        <div
                            class="mb-5 rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                            {{ session('status') }}
                        </div>
                    @endif


                    <!-- VALIDATION ERRORS -->

                    @if ($errors->any())

                        <div class="mb-5 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3">

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


                    <!-- =================================================
                         EMAIL
                    ================================================== -->

                    <div>

                        <label for="email" class="mb-2 block text-sm font-semibold text-charcoal">
                            E-mail
                        </label>


                        <div class="relative">

                            <!-- Email Icon -->

                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">

                                <svg class="h-5 w-5 text-charcoal/40" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />

                                </svg>

                            </div>


                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                autofocus autocomplete="email" placeholder="Enter your e-mail"
                                class="w-full rounded-xl border border-light-gray bg-white py-3.5 pl-11 pr-4 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10">

                        </div>

                    </div>


                    <!-- =================================================
                         PASSWORD
                    ================================================== -->

                    <div class="mt-5">

                        <div class="mb-2 flex items-center justify-between">

                            <label for="password" class="block text-sm font-semibold text-charcoal">
                                Password
                            </label>


                            <!-- Forgot Password -->

                            <a href="#" class="text-xs font-semibold text-primary transition hover:text-sky-blue">
                                Forgot password?
                            </a>

                        </div>


                        <div class="relative">

                            <!-- Lock Icon -->

                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">

                                <svg class="h-5 w-5 text-charcoal/40" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M7 10V7a5 5 0 0110 0v3M6 10h12a2 2 0 012 2v7a2 2 0 01-2 2H6a2 2 0 01-2-2v-7a2 2 0 012-2z" />

                                </svg>

                            </div>


                            <input type="password" id="password" name="password" required
                                autocomplete="current-password" placeholder="Enter your password"
                                class="w-full rounded-xl border border-light-gray bg-white py-3.5 pl-11 pr-12 text-sm text-charcoal outline-none transition placeholder:text-charcoal/35 focus:border-primary focus:ring-4 focus:ring-primary/10">


                            <!-- Show Password -->

                            <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center px-4 text-charcoal/40 transition hover:text-primary"
                                aria-label="Show password">

                                <svg id="eyeOpen" class="h-5 w-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />

                                    <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />

                                </svg>


                                <svg id="eyeClosed" class="hidden h-5 w-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.7 10.7 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3 3.8M6.2 6.2C3.7 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.6-.3 3.7-.8" />

                                </svg>

                            </button>

                        </div>

                    </div>


                    <!-- =================================================
                         REMEMBER ME
                    ================================================== -->

                    <div class="mt-5 flex items-center">

                        <label class="flex cursor-pointer items-center gap-2.5">

                            <input type="checkbox" name="remember"
                                class="h-4 w-4 rounded border-light-gray text-primary accent-primary focus:ring-primary/20">

                            <span class="text-xs text-charcoal/65 sm:text-sm">
                                Remember me
                            </span>

                        </label>

                    </div>


                    <!-- =================================================
                         LOGIN BUTTON
                    ================================================== -->

                    <button type="submit"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/20">

                        Log In

                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14m-6-6l6 6-6 6" />

                        </svg>

                    </button>


                    <!-- =================================================
                         REGISTRATION
                    ================================================== -->

                    <p class="mt-7 text-center text-sm text-charcoal/65">

                        Don't have an account?

                        <a href="{{ route('register') }}"
                            class="font-bold text-primary transition hover:text-sky-blue hover:underline">
                            Create an account
                        </a>

                    </p>

                </form>

            </div>


            <!-- =================================================
                 SECURITY / INFO
            ================================================== -->

            <div class="mt-6 flex items-start justify-center gap-2 text-center">

                <svg class="mt-0.5 h-4 w-4 shrink-0 text-leaf-green" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4z" />

                </svg>

                <p class="max-w-sm text-xs leading-5 text-charcoal/55">
                    Your account information is securely handled by
                    Shopleap.
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
         PASSWORD VISIBILITY
    ====================================================== -->

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');

        togglePassword.addEventListener('click', function() {

            const isPassword =
                passwordInput.type === 'password';

            passwordInput.type =
                isPassword ? 'text' : 'password';

            eyeOpen.classList.toggle(
                'hidden',
                isPassword
            );

            eyeClosed.classList.toggle(
                'hidden',
                !isPassword
            );

            togglePassword.setAttribute(
                'aria-label',
                isPassword ?
                'Hide password' :
                'Show password'
            );

        });
    </script>

</body>

</html>
