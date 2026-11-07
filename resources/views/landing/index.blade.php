<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopleap | Browse Categories &amp; Products</title>
    <meta name="description" content="Discover products from Shopleap sellers. Browse categories, shop with Cash on Delivery, or start selling on Shopleap.">
    <link rel="canonical" href="{{ route('home') }}">
    <meta property="og:title" content="Shopleap | Browse Categories &amp; Products">
    <meta property="og:description" content="Browse categories and discover products from Shopleap sellers.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:image" content="{{ asset('images/Shopleap-Logo.png') }}">
    <link rel="icon" href="{{ asset('images/Shopleap-Logo.png') }}">
    @vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js'])
</head>
<body x-data class="landing-page font-sans text-charcoal">
    <x-nav.guest :landing="true" class="landing-navigation" />
    <main id="home">
        <div class="site-container landing-content">
            <section class="landing-hero" aria-labelledby="hero-heading">
    <div class="landing-hero-copy">
        <p class="landing-eyebrow">A little discovery. An everyday delight.</p>
        <h1 id="hero-heading">Good finds.<br>For your <em>everyday.</em></h1>
        <p class="landing-intro">The things you need. The finds you didn't know you loved. Discover something for every corner of your life, all in one place.</p>
        <div class="landing-hero-actions"><a href="#discover" class="btn btn-primary">Explore the finds &rarr;</a><a href="#categories" class="landing-text-link">Browse categories &#8599;</a></div>
        <p class="landing-hero-note">From your next favorite outfit to a happier home.</p>
    </div>
    <div class="landing-hero-art" aria-hidden="true"><span class="landing-art-caption">GOOD FINDS. EVERYDAY JOY.</span><div class="landing-art-orbit"></div><span class="landing-art-spark">&#10035;</span><div class="landing-bag landing-bag-back"><span>the little<br>things.</span></div><div class="landing-bag landing-bag-front"><strong>s.</strong><span>shopleap</span></div><div class="landing-art-tag"><span>Made for your everyday</span><strong>Find your next favorite &#8599;</strong></div><span class="landing-art-bottom">A fresh way to find your kind of thing.</span></div>
</section>
<div class="landing-benefits" aria-label="Shopping on Shopleap">
    @foreach ([['01', 'Discover more', 'Find something for every part of your day.'], ['02', 'Pay on delivery', 'Cash on Delivery at checkout.'], ['03', 'Stay in the loop', 'Track orders from your account.'], ['04', 'Make it yours', 'Sign up to save your favorites.']] as [$number, $title, $description])
        <div class="landing-benefit"><span>{{ $number }}</span><div><h2>{{ $title }}</h2><p>{{ $description }}</p></div></div>
    @endforeach
</div>
<section id="categories" aria-labelledby="categories-heading">
                <div class="landing-section-heading"><div><p class="landing-eyebrow">A world of possibilities</p><h2 id="categories-heading">What are you looking for?</h2><p>Big plans or little essentials. Begin with what you love.</p></div><a href="#discover" class="landing-text-link">Explore all products &rarr;</a></div>
                <div class="landing-grid landing-category-grid" role="region" aria-label="Product categories, scroll for more" tabindex="0">
                    @forelse ($categories as $category)
                        <x-landing.category-tile :category="$category" :index="$loop->index" />
                    @empty
                        <p class="col-span-full text-sm text-charcoal/70">Categories will appear here soon.</p>
                    @endforelse
                </div>
            </section>
            <section x-reveal id="deals" aria-labelledby="products-heading">
                <div class="landing-section-heading"><div><p class="landing-eyebrow">The discovery edit</p><h2 id="products-heading">Featured products</h2><p>A few finds to get your inspiration going.</p></div><a href="#discover" class="landing-text-link">Keep exploring &rarr;</a></div>
                <x-landing.product-grid :products="$bestSellers" />
            </section>
            <section x-reveal id="discover" aria-labelledby="discover-heading">
                <div class="landing-section-heading"><div><p class="landing-eyebrow">Something worth a second look</p><h2 id="discover-heading">Daily Discover</h2><p class="landing-section-description">Take a look around. Your next favorite could be right here.</p></div>@if (request()->filled('search') || request()->filled('category'))<a href="{{ route('home') }}#discover" class="btn btn-outline">Clear filters</a>@endif</div>
                @if (request()->filled('search') || request()->filled('category'))<p class="mb-4 text-sm">Results for {{ request('search') ?: request('category') }}</p>@endif
                <x-landing.product-grid :products="$recommended" />
            </section>
            <section x-reveal id="how-it-works" aria-labelledby="how-heading">
                <div class="landing-section-heading"><div><p class="landing-eyebrow">From discovery to your doorstep</p><h2 id="how-heading">Good finds. Simple steps.</h2><p>A little browsing is all it takes to begin.</p></div><a href="{{ route('register.buyer') }}" class="landing-text-link">Create an account &rarr;</a></div>
                <ol class="landing-steps">
                    @foreach (['Browse products and find something you love.', 'Create an account and order with Cash on Delivery.', 'Track delivery from your account.'] as $step)
                        <li class="rounded border border-line p-5"><span class="mb-3 block text-lg font-bold text-primary">0{{ $loop->iteration }}</span>{{ $step }}</li>
                    @endforeach
                </ol>
            </section>
                        <section x-reveal class="landing-seller" aria-labelledby="seller-heading"><div><p class="landing-eyebrow">Your products. Their next great find.</p><h2 id="seller-heading">A new chapter for your business starts here.</h2><p>Bring what you do best to Shopleap. Apply for a seller account and give shoppers something new to discover.</p><a href="{{ route('register.seller') }}" class="btn btn-primary">Become a seller &rarr;</a></div><div class="landing-seller-aside"><span aria-hidden="true">&#8599;</span><h3>Let's grow, together.</h3><p>Have a delivery business? Explore becoming a logistics partner.</p><a href="{{ \App\Support\Portal::url('logistics') }}" class="landing-text-link">Partner with Shopleap &rarr;</a></div></section>
            <section x-reveal class="landing-faq" aria-labelledby="faq-heading"><div><p class="landing-eyebrow">A little clarity</p><h2 id="faq-heading">Before you explore.</h2><p>A few things to help you get started.</p></div><div>
                @foreach ([['Can I browse without an account?', 'Yes. Explore categories, search, and browse featured products here. When you are ready to save favorites or place an order, log in or create a buyer account.'], ['How can I pay for my order?', 'Shopleap offers Cash on Delivery. Review your order details and total at checkout before placing your order.'], ['Where can I track my order?', 'Log in to your buyer account and visit Purchases to check your order and delivery progress.'], ['How do I start selling?', 'Choose Become a seller and complete the seller registration form to apply for a seller account.']] as [$question, $answer])
                    <details><summary>{{ $question }}<span aria-hidden="true">+</span></summary><p>{{ $answer }}</p></details>
                @endforeach
            </div></section>

        </div>
    </main>
        <footer class="landing-footer"><div class="site-container"><div class="landing-footer-main"><div class="landing-footer-brand"><x-nav.brand /><p>A little discovery. An everyday delight.<br>Your next favorite starts here.</p></div><nav aria-label="Explore"><h2>Explore</h2><a href="#categories">Shop by category</a><a href="#deals">Featured products</a><a href="#discover">Daily Discover</a></nav><nav aria-label="Join Shopleap"><h2>Grow with us</h2><a href="{{ route('register.buyer') }}">Create an account</a><a href="{{ route('register.seller') }}">Become a seller</a><a href="{{ \App\Support\Portal::url('logistics') }}">Logistics partner</a></nav><nav aria-label="Information"><h2>Good to know</h2><a href="#how-it-works">How it works</a><a href="{{ route('terms') }}">Terms and Conditions</a><a href="{{ route('privacy') }}">Privacy Policy</a></nav></div><div class="landing-footer-bottom"><p>&copy; {{ now()->year }} Shopleap. All rights reserved.</p><a href="#home">Back to top &uarr;</a></div></div></footer>
</body>
</html>


