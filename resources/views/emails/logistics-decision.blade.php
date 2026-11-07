<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>ShopLeap Logistics application</title></head>
<body style="font-family:Arial,sans-serif;background:#F1F5F9;color:#0B2545;padding:24px">
    <main style="max-width:560px;margin:auto;background:white;padding:28px;border-radius:12px">
        <h1>ShopLeap Logistics</h1>
        @if ($approved)
            <h2>Your application is approved</h2>
            <p>You can now manage your riders and start delivering ShopLeap orders.</p>
            <p><a href="{{ $loginUrl }}">Log in to your Logistics portal</a></p>
        @else
            <h2>Your application needs an update</h2>
            <p>{{ $reason }}</p>
            <p>Log in with your existing account, update your documents and resubmit for review.</p>
            <p><a href="{{ $applicationUrl }}">Review and resubmit your application</a></p>
        @endif
    </main>
</body>
</html>
