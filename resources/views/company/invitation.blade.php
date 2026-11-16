<x-logistics.public-layout title="Set your hub password" noindex>
    <section class="portal-container portal-section">
        <div class="mx-auto max-w-lg rounded-sm border border-line bg-surface p-6 text-charcoal">
            <h1 class="text-xl font-semibold">Set your password</h1>
            <p class="mt-2">Manage {{ $hub->business_name }} as {{ $hub->user->email }}.</p>
            @if ($errors->any())<p role="alert" class="mt-4 text-danger">{{ $errors->first() }}</p>@endif
            <form method="POST" action="{{ request()->fullUrl() }}" class="mt-5 space-y-4">
                @csrf
                <label class="block text-sm">Password<input type="password" name="password" autocomplete="new-password" required minlength="8" class="mt-1 block w-full rounded-sm border border-line p-3"></label>
                <label class="block text-sm">Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" required minlength="8" class="mt-1 block w-full rounded-sm border border-line p-3"></label>
                <button type="submit" class="btn btn-primary">Set password</button>
            </form>
        </div>
    </section>
</x-logistics.public-layout>
