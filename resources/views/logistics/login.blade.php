<x-logistics.public-layout title="Log in" noindex>
    <div class="portal-auth-grid portal-container">
        <section class="portal-auth-intro"><p class="portal-eyebrow">Welcome back, partner</p><h1>Your deliveries.<br>Your team.<br>One workspace.</h1><p>Sign in to manage your logistics operations, riders and deliveries.</p></section>
        <section class="portal-form-card">
            <h2>Log in to Logistics</h2><p>Use your company's Logistics account.</p>
            @if (session('status'))<p role="status" class="portal-notice">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="portal-error">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login') }}" class="portal-form">
                @csrf
                <label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                <label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
                <label class="portal-check"><input type="checkbox" name="remember" @checked(old('remember'))> Remember me</label>
                <button class="portal-button" type="submit">Log in</button>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </form>
            <p class="portal-form-foot">New to ShopLeap Logistics? <a href="{{ route('register.logistics') }}">Register your company</a></p>
        </section>
    </div>
</x-logistics.public-layout>
