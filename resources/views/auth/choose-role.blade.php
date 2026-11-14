<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose account | Shopleap</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-ice-blue text-charcoal">
    <x-nav.guest />
    <main class="mx-auto max-w-lg px-4 py-12">
        <section class="rounded-md border border-line bg-surface p-6">
            <x-logo :href="route('home')" image="images/Shopleap-Logo.png" class="public-brand" />
            <h1 class="mt-6 text-xl font-bold">Choose your account</h1>
            <p class="mt-2 text-sm">Your password matches these accounts. Choose one to continue.</p>
            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-3">
                @csrf
                @foreach ($roles as $role)
                    <button type="submit" name="role" value="{{ $role }}" class="block w-full rounded-md bg-primary px-4 py-3 font-semibold text-surface">Continue as {{ ucfirst($role) }}</button>
                @endforeach
            </form>
            <a href="{{ route('login') }}" class="mt-6 block text-sm text-primary">Back to login</a>
        </section>
    </main>
    <x-footer />
</body>
</html>
