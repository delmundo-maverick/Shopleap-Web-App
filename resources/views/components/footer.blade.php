<footer class="mt-auto border-t border-light-gray bg-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-2 px-3 py-6 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-xs text-charcoal/60">
            © {{ date('Y') }} Shopleap.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-4 text-xs text-charcoal/60">
            <a href="{{ \App\Support\Portal::route('main', 'terms') }}" class="transition hover:text-primary">Terms and Conditions</a>
            <a href="{{ \App\Support\Portal::route('main', 'privacy') }}" class="transition hover:text-primary">Privacy Policy</a>
            <span>Shop Smart. Shop Simple.</span>
        </div>
    </div>
</footer>
