@props(['title'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | Shopleap Seller</title>
    @vite(['resources/css/app.css', 'resources/css/pages/seller/products.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-charcoal" x-data="productWorkspace(@js(['message' => session('status'), 'error' => session('error') ?: ($errors->any() ? $errors->first() : null)]))"
    @submit="submit($event)" @pageshow.window="busy = false" @offline.window="notify('You are offline. Reconnect before saving.', true)">
    <div class="frost-surface pointer-events-none" aria-hidden="true"></div>
    <div class="flex min-h-screen">
        <x-navbars.seller active="inventory" />
        <div class="min-w-0 flex-1">
            <x-seller.page-header :title="$title">{{ $actions ?? '' }}</x-seller.page-header>
            <main class="space-y-5 p-4 sm:p-6" :aria-busy="busy">
                <noscript><p class="rounded-xl bg-warning/10 p-4">JavaScript is needed for previews, bulk actions and delete confirmations. Standard save forms remain available.</p></noscript>
                <div x-cloak x-show="busy" role="status" class="rounded-xl bg-primary/10 p-3 text-sm text-primary">Working... Please wait.</div>
                <div x-cloak x-show="formErrors.length" role="alert" tabindex="-1" x-ref="errors" class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger">
                    <p class="font-semibold">Please correct the following:</p>
                    <template x-for="(error, index) in formErrors" :key="index"><p x-text="error"></p></template>
                </div>
                @if ($errors->any())
                    <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger">
                        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif
                @if (session('error'))<p role="alert" class="rounded-xl bg-danger/10 p-4 text-danger">{{ session('error') }}</p>@endif
                {{ $slot }}
            </main>
        </div>
    </div>
    <div x-cloak x-show="toast" x-transition role="status" aria-live="polite" class="fixed bottom-5 right-5 z-[100] max-w-sm rounded-xl border bg-white p-4 shadow-xl" :class="toastError ? 'border-danger text-danger' : 'border-success text-success'">
        <span x-text="toast"></span><button type="button" @click="toast = ''" class="ml-3" aria-label="Dismiss notification">&times;</button>
    </div>
    <dialog x-ref="confirmation" @cancel="cancelConfirmation()" @click="if ($event.target === $refs.confirmation) cancelConfirmation()" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border border-line bg-white p-6 text-charcoal shadow-xl backdrop:bg-charcoal/50">
        <h2 class="text-lg font-semibold">Delete product<span x-show="confirmationCount > 1">s</span>?</h2>
        <p class="mt-2 text-sm text-charcoal/65">This permanently deletes the selected listing<span x-show="confirmationCount > 1">s</span>. Order history is retained.</p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" class="btn-soft" autofocus @click="cancelConfirmation()">Cancel</button>
            <button type="button" class="btn-soft btn-soft--danger" @click="confirmDeletion()">Delete</button>
        </div>
    </dialog>
</body>
</html>
