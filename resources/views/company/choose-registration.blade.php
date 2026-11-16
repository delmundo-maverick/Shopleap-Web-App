<x-logistics.public-layout title="Create account" noindex>
    <section class="portal-container portal-section">
        <h1 class="text-2xl font-semibold">Create your logistics account</h1>
        <p class="mt-3">Choose the account that fits your business.</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <a href="{{ route('register.logistics') }}" class="rounded-sm border border-line bg-surface p-6 text-charcoal"><h2 class="text-lg font-semibold">Register as a Hub</h2><p class="mt-2">Register an independent hub using the existing application form.</p></a>
            <a href="{{ route('company.register') }}" class="rounded-sm border border-line bg-surface p-6 text-charcoal"><h2 class="text-lg font-semibold">Register as a Company</h2><p class="mt-2">Create a company account to manage your own hub network.</p></a>
        </div>
    </section>
</x-logistics.public-layout>
