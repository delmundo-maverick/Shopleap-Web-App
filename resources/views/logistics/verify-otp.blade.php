<x-logistics.public-layout title="Verify email" noindex>
    <section class="portal-container portal-section">
        <div class="portal-form-card portal-application">
            <h1>Verify your email</h1><p>Enter the six-digit code sent to {{ $email }}.</p>
            @if ($errors->any())<p role="alert" class="portal-error">{{ $errors->first() }}</p>@endif
            @if (session('status'))<p role="status" class="portal-notice">{{ session('status') }}</p>@endif
            <form method="POST" action="{{ route('register.otp.verify', $role) }}" class="portal-form">
                @csrf<label for="otp">Verification code</label><input id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                <button class="portal-button" type="submit">Verify email</button>
            </form>
            <form method="POST" action="{{ route('register.resend-otp', $role) }}">@csrf<button class="portal-button portal-button-outline" type="submit">Resend code</button></form>
        </div>
    </section>
</x-logistics.public-layout>
