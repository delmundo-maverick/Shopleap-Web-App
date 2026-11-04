<footer class="mt-auto border-t border-light-gray bg-white">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-5 py-6 sm:flex-row">
        <p class="text-xs text-charcoal/60">
            © {{ date('Y') }} Shopleap.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-4 text-xs text-charcoal/60">
            <a href="{{ route('terms') }}" class="transition hover:text-primary">Terms and Conditions</a>
            <a href="{{ route('privacy') }}" class="transition hover:text-primary">Privacy Policy</a>
            <span>Shop Smart. Shop Simple.</span>
        </div>
    </div>
</footer>
