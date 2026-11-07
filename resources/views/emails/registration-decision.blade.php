<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>ShopLeap registration update</title></head>
<body style="margin:0;background:#F8FAFC;color:#2E3A46;font-family:Arial,sans-serif;padding:24px">
    <main style="max-width:560px;margin:auto;background:white;border:1px solid #DDE3EA;border-radius:8px;padding:28px">
        <h1 style="color:#1A5FB4">ShopLeap</h1>
        <h2>{{ $approved ? 'Your registration is approved' : 'Your registration was not approved' }}</h2>
        <p>Hello {{ $name }},</p>
        @if ($approved)
            <p>Your {{ $role }} registration has been approved. You can now sign in to your account.</p>
        @else
            <p>Our team reviewed your {{ $role }} registration and could not approve it.</p>
            <p><strong>Reason:</strong> {{ $reason }}</p>
            <p>Please contact ShopLeap support if you need help with this decision.</p>
        @endif
        <p><a href="{{ $loginUrl }}" style="color:#1A5FB4">{{ $approved ? 'Log in to ShopLeap' : 'Visit ShopLeap' }}</a></p>
    </main>
</body>
</html>
