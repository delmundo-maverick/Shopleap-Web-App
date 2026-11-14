@props(['role'])
@php
    $accountLabel = match ($role) {
        'buyer' => 'Buyer registration',
        'seller' => 'Seller registration',
        'logistics' => 'Logistics registration',
    };
    $homeUrl = $role === 'logistics' ? \App\Support\Portal::url('logistics') : route('home');
@endphp
<header {{ $attributes->merge(['class' => 'registration-header']) }}>
    <div class="registration-header-inner">
        <div class="registration-header-brand">
            <x-logo :href="$homeUrl" image="images/Shopleap-Logo-White.png" />
            <span class="registration-header-label">{{ $accountLabel }}</span>
        </div>
        <nav class="registration-header-account" aria-label="Account">
            <span>Already have an account?</span>
            <a href="{{ route('login') }}">Log in <span aria-hidden="true">&rarr;</span></a>
        </nav>
    </div>
</header>
