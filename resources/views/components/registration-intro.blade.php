@props(['role'])
@php
    $introductions = [
        'buyer' => ['For your everyday', 'Shop Smart.', 'Shop Simple.', 'Your next favorite starts here. Create an account to save your finds, place orders, and follow every delivery.', ['Discover products you love', 'Save favorites for later', 'Order with Cash on Delivery']],
        'seller' => ['A new chapter for your business', 'Good products.', 'Great possibilities.', 'Bring what you do best to Shopleap. Set up your seller account and give shoppers something new to discover.', ['Build your online storefront', 'Manage products and orders', 'Follow your business growth']],
        'logistics' => ['Your network. More possibilities.', 'Deliver more.', 'Grow together.', 'Put your local knowledge to work. Register your sorting center and connect your delivery team with Shopleap.', ['Choose your service regions', 'Bring your riders together', 'Track parcels from pickup to doorstep']],
    ];
    [$eyebrow, $heading, $accent, $description, $benefits] = $introductions[$role];
@endphp
<aside class="registration-intro" aria-labelledby="registration-heading">
    <p class="registration-eyebrow">{{ $eyebrow }}</p>
    <h1 id="registration-heading">{{ $heading }}<br><em>{{ $accent }}</em></h1>
    <p class="registration-description">{{ $description }}</p>
    <ul class="registration-benefits">
        @foreach ($benefits as $benefit)
            <li><span aria-hidden="true">&#10003;</span>{{ $benefit }}</li>
        @endforeach
    </ul>
    <div class="registration-next">
        <p class="registration-eyebrow">A simple start</p>
        <ol>
            <li><span>01</span>Complete your details</li>
            <li><span>02</span>Verify your email</li>
            <li><span>03</span>We'll review your application</li>
        </ol>
        <p>Have an account? <a href="{{ route('login') }}">Log in &rarr;</a></p>
    </div>
</aside>
