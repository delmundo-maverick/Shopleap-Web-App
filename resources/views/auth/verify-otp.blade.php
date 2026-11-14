<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Verify your email | ShopLeap</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ice-blue text-charcoal">
    <x-nav.guest />
    <main class="mx-auto max-w-lg px-4 py-12">
        <section class="rounded-2xl border border-line bg-white p-6">
            <h1 class="text-2xl font-bold">Verify your email</h1>
            <p class="mt-3 text-sm">Enter the six-digit code sent to {{ $email }}.</p>
            @if ($errors->any())<p role="alert" class="mt-4 text-sale-red">{{ $errors->first() }}</p>@endif
            @if (session('status'))<p role="status" class="mt-4">{{ session('status') }}</p>@endif
            <form x-data="{ submitting: false }" @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" @pageshow.window="submitting = false" :aria-busy="submitting" method="POST" action="{{ route('register.otp.verify', $role) }}" class="mt-6 grid gap-4">
                @csrf
                <label for="otp" class="text-sm font-semibold">Verification code</label>
                <input id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required class="w-full rounded-xl border border-line p-3">
                <button class="rounded-xl bg-primary px-4 py-3 font-semibold text-white" type="submit" :disabled="submitting">Verify email</button>
            </form>
            <form x-data="{ submitting: false }" @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" @pageshow.window="submitting = false" :aria-busy="submitting" method="POST" action="{{ route('register.resend-otp', $role) }}" class="mt-4">
                @csrf
                <button type="submit" :disabled="submitting" class="py-3 text-sm font-semibold text-primary">Resend code</button>
            </form>
        </section>
    </main>
    <x-footer />
</body>
</html>
