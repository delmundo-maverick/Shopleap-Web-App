<header class="portal-header">
    <div class="portal-container">
        <x-logo :href="\App\Support\Portal::url('logistics')" label="Logistics" class="public-brand" />
        <nav class="portal-sections" aria-label="About the partnership">
            @foreach (['how-it-works' => 'How it works', 'coverage' => 'Coverage', 'requirements' => 'Requirements', 'faq' => 'FAQ'] as $anchor => $label)
                <a href="{{ \App\Support\Portal::url('logistics') }}#{{ $anchor }}">{{ $label }}</a>
            @endforeach
        </nav>
        <nav class="portal-actions" aria-label="Account">
            <a class="portal-button portal-button-outline" href="{{ \App\Support\Portal::url('logistics', 'login') }}">Log In</a>
            <a class="portal-button" href="{{ route('company.register.choose') }}">Create account</a>
        </nav>
    </div>
</header>
