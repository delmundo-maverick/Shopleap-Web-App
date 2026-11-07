@props(['title', 'description' => 'Partner with ShopLeap to deliver orders across the Philippines.', 'noindex' => false])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    @if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
    <link rel="icon" type="image/png" href="{{ asset('images/Shopleap-Logo.png') }}">
    <title>{{ $title }} | ShopLeap Logistics</title>
    @vite(['resources/css/app.css', 'resources/css/pages/logistics/portal.css'])
</head>
<body class="logistics-portal">
    <a class="portal-skip" href="#content">Skip to content</a>
    <x-logistics.public-header />
    <main id="content">{{ $slot }}</main>
    <footer class="portal-footer">
        <div class="portal-container">
            <p>&copy; {{ now()->year }} ShopLeap Logistics</p>
            <nav aria-label="Footer">
                <a href="{{ \App\Support\Portal::url('main') }}">Back to ShopLeap</a>
                <a href="{{ route('terms') }}">Terms</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
            </nav>
        </div>
    </footer>
</body>
</html>
