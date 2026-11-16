<x-logistics.public-layout title="Deliver with ShopLeap" description="Grow your delivery business with ShopLeap Logistics. Register your company, create branches and manage deliveries across the Philippines.">
    <section class="portal-hero">
        <div class="portal-container portal-hero-grid">
            <div>
                <p class="portal-eyebrow">Your network. More possibilities.</p>
                <h1>Keep the Philippines moving.<br><span>Deliver with ShopLeap.</span></h1>
                <p class="portal-lead">Deliver ShopLeap orders across the Philippines with your own riders and a portal that keeps every delivery in view.</p>
                <div class="portal-cta">
                    <a class="portal-button" href="{{ route('company.register') }}">Register your company <span aria-hidden="true">&rarr;</span></a>
                    <a class="portal-button portal-button-light" href="{{ route('login') }}">Log in</a>
                </div>
                <p class="portal-small">For logistics companies ready to go the extra mile. Grow with us.</p>
            </div>
            <aside class="portal-route-card" aria-label="Your delivery network">
                <p class="portal-eyebrow">Built for your next delivery</p>
                <div class="portal-route-line"><span>01</span><div><h2>Your company</h2><p>Choose where you deliver.</p></div></div>
                <div class="portal-route-line"><span>02</span><div><h2>Your riders</h2><p>Bring your team together.</p></div></div>
                <div class="portal-route-line"><span>03</span><div><h2>Every order, in view</h2><p>From pickup to doorstep.</p></div></div>
                <p class="portal-network-note">One connected delivery workspace.</p>
            </aside>
        </div>
    </section>
    <section id="how-it-works" class="portal-section portal-container">
        <p class="portal-eyebrow">A clear path to partnership</p><h2>Get moving in four steps.</h2>
        <div class="portal-grid portal-grid-four">
            @foreach ([
                ['Register your company', 'Create the mother account with your company details, representative details and verification documents.'],
                ['Verify email', 'Enter the code sent to your email to submit your application.'],
                ['Get approved', 'Our team reviews your application and emails the decision.'],
                ['Create your branches', 'Add hubs and invite their managers. Each hub manages its rider applications and deliveries.'],
            ] as [$heading, $copy])
                <article class="portal-card"><span class="portal-step">0{{ $loop->iteration }}</span><h3>{{ $heading }}</h3><p>{{ $copy }}</p></article>
            @endforeach
        </div>
    </section>
    <section class="portal-section portal-benefits">
        <div class="portal-container">
            <p class="portal-eyebrow">Built around your operations</p><h2>Your business. Room to grow.</h2>
            <div class="portal-grid portal-grid-four">
                @foreach ([
                    ['Steady order volume', 'Connect your delivery business with ShopLeap orders in your service areas.'],
                    ['Manage your network', 'Create branches, manage hub accounts and oversee your delivery team.'],
                    ['Track deliveries', 'Follow pickups, parcel movement and delivery progress in one place.'],
                    ['Simple onboarding', 'Register online, verify your email and follow a clear approval process.'],
                ] as [$heading, $copy])
                    <article class="portal-card"><h3>{{ $heading }}</h3><p>{{ $copy }}</p></article>
                @endforeach
            </div>
        </div>
    </section>
    <section id="coverage" class="portal-section portal-container">
        <p class="portal-eyebrow">Local knowledge. Nationwide ambition.</p><h2>Build your branch coverage.</h2>
        <p class="portal-copy">After company approval, create branches with their province, municipality and covered barangays. Each barangay belongs to one hub, keeping pickups and deliveries assigned to the right branch.</p>
        <ul class="portal-regions" aria-label="Service regions">@foreach (config('logistics.regions') as $region)<li>{{ $region }}</li>@endforeach</ul>
    </section>
    <section id="requirements" class="portal-section portal-benefits">
        <div class="portal-container portal-two-column">
            <div><p class="portal-eyebrow">Ready when you are</p><h2>Your company.<br>One mother account.</h2><p class="portal-copy">Provide your company and representative details, upload these four documents, and verify your email for administrator review. Each document must be JPG, PNG or PDF, up to 5 MB. Branches are created inside the approved company account.</p></div>
            <ul class="portal-document-list">
                @foreach (\App\Models\Company::DOCUMENTS as $document)<li>{{ $document['label'] }}</li>@endforeach
            </ul>
        </div>
    </section>
    <section id="faq" class="portal-section portal-container">
        <p class="portal-eyebrow">Before you hit the road</p><h2>Your questions, answered.</h2>
        <div class="portal-faq">
            @foreach ([
                ['How long does approval take?', 'Our team reviews your company application and documents after email verification. Timing depends on the application and any clarifications needed. We will email you when a decision is made.'],
                ['Who can register?', 'An authorized representative can register a logistics company mother account. Branch managers do not register separately.'],
                ['How do branches get accounts?', 'After company approval, open Hubs in your company dashboard and choose Create hub. The branch manager receives an invitation to set their own password.'],
                ['How are riders added?', 'Riders use the ShopLeap Rider app to apply to a branch. That branch manager reviews and approves rider applications in the hub portal.'],
                ['What information do I need?', 'Your company name, PSGC province, municipality or city, barangay, street address and service regions. Provide the authorized representative’s first and last name, optional middle initial, sex, birthday and contact number, plus an account email and password. Upload the representative’s valid ID, company business permit, SEC certificate and BIR Form 2303. A company logo is optional.'],
                ['How can I get support?', 'After signing in, use Help in your Logistics portal to contact the support team.'],
            ] as [$question, $answer])
                <details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
            @endforeach
        </div>
    </section>
    <section class="portal-final-cta portal-container"><div><h2>Your next chapter starts here.</h2><p>Put your delivery network to work with ShopLeap.</p></div><a class="portal-button" href="{{ route('company.register') }}">Register your company &rarr;</a></section>
</x-logistics.public-layout>
