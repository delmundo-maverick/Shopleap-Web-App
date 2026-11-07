<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password | Shopleap</title>
    @if (\App\Support\Portal::isLogistics())
        @vite('resources/css/pages/logistics/portal.css')
        <meta name="robots" content="noindex, nofollow">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/auth/password-recovery.js'])
</head>
<body class="{{ \App\Support\Portal::isLogistics() ? 'logistics-portal' : '' }} min-h-dvh bg-ice-blue text-charcoal">
@if (\App\Support\Portal::isLogistics())
    <x-logistics.public-header />
@else
    <x-nav.guest />
@endif
<main class="mx-auto flex min-h-dvh max-w-lg items-center px-4 py-10">
    <section class="w-full rounded-md border border-charcoal/10 bg-white p-6 sm:p-8">
        <a href="{{ route('login') }}" class="font-bold text-primary">Shopleap</a>
        <h1 class="mt-6 text-2xl font-bold">Reset your password</h1>
        <ol class="my-6 grid grid-cols-3 gap-2 text-xs" aria-label="Password recovery progress">
            @foreach ([1 => 'Email', 2 => 'Verify OTP', 3 => 'New password'] as $number => $label)
                <li class="border-t-4 pt-2 {{ $step >= $number ? 'border-primary text-primary' : 'border-charcoal/15' }}" @if ($step === $number) aria-current="step" @endif>{{ $number }}. {{ $label }}</li>
            @endforeach
        </ol>
        @if (session('status'))
            <p role="status" class="mb-4 rounded border border-primary/20 bg-ice-blue p-3 text-sm">{{ session('status') }}</p>
        @endif
        @if ($step === 1)
            <p class="mb-5 text-sm">Enter the email you use to log in.</p>
            <form method="POST" action="{{ route('password.email') }}" data-recovery-form class="space-y-4">
                @csrf
                <label for="email" class="block text-sm font-semibold">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required maxlength="255" value="{{ old('email') }}" class="w-full rounded border border-charcoal/25 px-3 py-3">
                @error('email') <p role="alert" class="text-sm text-sale-red">{{ $message }}</p> @enderror
                <button class="btn btn-primary w-full" data-loading="Sending?">Send OTP</button>
            </form>
        @elseif ($step === 2)
            <p class="mb-5 text-sm">Enter the six-digit code sent to {{ $maskedEmail }}. It expires in 10 minutes.</p>
            <form method="POST" action="{{ route('password.verify.store') }}" data-recovery-form class="space-y-4">
                @csrf
                <label for="otp" class="block text-sm font-semibold">Verification code</label>
                <input id="otp" name="otp" required pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="w-full rounded border border-charcoal/25 px-3 py-3" data-otp-value>
                <div class="hidden grid-cols-6 gap-2" data-otp-boxes>
                    @for ($digit = 0; $digit < 6; $digit++)
                        <input type="text" inputmode="numeric" autocomplete="{{ $digit === 0 ? 'one-time-code' : 'off' }}" maxlength="6" aria-label="Digit {{ $digit + 1 }}" data-otp-digit class="min-w-0 rounded border border-charcoal/25 py-3 text-center text-xl focus:border-primary focus:outline-primary">
                    @endfor
                </div>
                @error('otp') <p role="alert" class="text-sm text-sale-red">{{ $message }}</p> @enderror
                <button class="btn btn-primary w-full" data-loading="Verifying?">Verify OTP</button>
            </form>
            <form method="POST" action="{{ route('password.resend') }}" data-recovery-form class="mt-4">
                @csrf
                <button class="btn btn-secondary w-full" data-resend data-cooldown="{{ $cooldown }}" data-loading="Sending?">Resend OTP</button>
                @error('email') <p role="alert" class="mt-2 text-sm text-sale-red">{{ $message }}</p> @enderror
            </form>
            <a href="{{ route('password.request') }}" class="mt-4 block text-sm text-primary">Use a different email</a>
        @else
            <p class="mb-5 text-sm">Choose a new password of at least eight characters.</p>
            <form method="POST" action="{{ route('password.update') }}" data-recovery-form class="space-y-4">
                @csrf
                <label for="password" class="block text-sm font-semibold">New password</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded border border-charcoal/25 px-3 py-3">
                @error('password') <p role="alert" class="text-sm text-sale-red">{{ $message }}</p> @enderror
                <label for="password_confirmation" class="block text-sm font-semibold">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded border border-charcoal/25 px-3 py-3">
                <button class="btn btn-primary w-full" data-loading="Saving?">Reset password</button>
            </form>
        @endif
        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-primary">Back to login</a>
    </section>
</main>
</body>
</html>
