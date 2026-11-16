<x-logistics.public-layout title="Reset your hub manager password" noindex>
    <section class="portal-container portal-section">
        <div class="mx-auto max-w-lg rounded-sm border border-line bg-surface p-6 text-charcoal">
            <h1 class="text-xl font-semibold">Reset your password</h1>
            <p class="mt-2">Set a new password for {{ $hub->user->email }} at {{ $hub->business_name }}.</p>
            @if ($errors->any())<p role="alert" class="mt-4 text-danger">{{ $errors->first() }}</p>@endif
            <form method="POST" action="{{ request()->fullUrl() }}" class="mt-5 space-y-4">
                @csrf
                <label class="block text-sm font-medium">New password<input type="password" name="password" autocomplete="new-password" minlength="8" required class="mt-1 block w-full rounded-sm border border-line p-3"></label>
                <label class="block text-sm font-medium">Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required class="mt-1 block w-full rounded-sm border border-line p-3"></label>
                <button type="submit" class="btn btn-primary">Reset password</button>
            </form>
        </div>
    </section>
</x-logistics.public-layout>
