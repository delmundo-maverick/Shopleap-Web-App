<header class="portal-header">
    <div class="portal-container">
        <a class="portal-brand" href="{{ \App\Support\Portal::url('logistics') }}">
            <img src="{{ asset('images/Shopleap-Logo.png') }}" width="36" height="36" alt="">
            <span>ShopLeap <strong>Logistics</strong></span>
        </a>
        <nav class="portal-sections" aria-label="About the partnership">
            @foreach (['how-it-works' => 'How it works', 'coverage' => 'Coverage', 'requirements' => 'Requirements', 'faq' => 'FAQ'] as $anchor => $label)
                <a href="{{ \App\Support\Portal::url('logistics') }}#{{ $anchor }}">{{ $label }}</a>
            @endforeach
        </nav>
        <nav class="portal-actions" aria-label="Account">
            <a class="portal-button portal-button-outline" href="{{ \App\Support\Portal::url('logistics', 'login') }}">Log In</a>
            <a class="portal-button" href="{{ route('register.logistics') }}">Register</a>
        </nav>
    </div>
</header>
