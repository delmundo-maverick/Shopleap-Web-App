@props(['id' => 'logout'])
<template x-teleport="body">
    <div x-show="logoutOpen" x-cloak x-transition.opacity class="nav-logout-overlay" @click.self="cancelLogout()">
        <section x-ref="logoutDialog" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-description" tabindex="-1"
            class="w-full max-w-sm rounded-sm border border-line bg-white p-5 text-charcoal">
            <svg aria-hidden="true" class="mb-3 size-7 text-sale-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H5v14h4m5-14 7 7-7 7m7-7H9" /></svg>
            <h2 id="{{ $id }}-title" class="text-lg font-bold">Log out?</h2>
            <p id="{{ $id }}-description" class="mt-2 text-sm text-charcoal/65">You will need to sign in again to access your account.</p>
            <form method="POST" action="{{ route('logout') }}" @submit.prevent="confirmLogout($el)" class="mt-5 flex gap-3">
                @csrf
                <button type="button" class="btn btn-outline min-h-10 flex-1" :disabled="logoutSubmitting" @click="cancelLogout()">Cancel</button>
                <button type="submit" class="btn btn-danger min-h-10 flex-1" :disabled="logoutSubmitting" :aria-busy="logoutSubmitting" x-text="logoutSubmitting ? 'Logging out…' : 'Log out'">Log out</button>
            </form>
        </section>
    </div>
</template>
