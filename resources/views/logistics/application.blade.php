<x-logistics.public-layout title="Application status" noindex>
    <section class="portal-container portal-section">
        <div class="portal-form-card portal-application">
            <p class="portal-eyebrow">{{ $profile->business_name }}</p>
            <h1>{{ $profile->status === 'rejected' ? 'Update your application' : 'Pending approval' }}</h1>
            @if (session('status'))<p role="status" class="portal-notice">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="portal-error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if ($profile->status === 'rejected')
                <p>Review the feedback, update your company details and upload your documents again.</p>
                <p class="portal-notice">{{ $profile->rejection_reason }}</p>
                <form method="POST" action="{{ route('logistics.application.resubmit') }}" enctype="multipart/form-data" class="portal-form">
                    @csrf
                    <label for="business_name">Business / hub name</label><input id="business_name" name="business_name" required maxlength="150" value="{{ old('business_name', $profile->business_name) }}">
                    <label for="company_name">Company name</label><input id="company_name" name="company_name" required maxlength="150" value="{{ old('company_name', $profile->company_name) }}">
                    <x-logistics.application-fields :selected="old('service_regions', $profile->service_regions ?? [])" />
                    <button type="submit" class="portal-button">Resubmit for approval</button>
                </form>
            @else
                <p>Your email is verified and your application is with our review team. We will email you when it is approved or needs an update.</p>
            @endif
            <p><a href="{{ route('help.index') }}">Contact support</a></p>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="portal-button portal-button-outline" type="submit">Log out</button></form>
        </div>
    </section>
</x-logistics.public-layout>
