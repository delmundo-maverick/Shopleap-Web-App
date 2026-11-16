<x-logistics.public-layout title="Deliver with ShopLeap" description="Grow your delivery business with ShopLeap Logistics. Register your company, choose your service regions and manage deliveries across the Philippines.">
    <section class="portal-hero">
        <div class="portal-container portal-hero-grid">
            <div>
                <p class="portal-eyebrow">Your network. More possibilities.</p>
                <h1>Keep the Philippines moving.<br><span>Deliver with ShopLeap.</span></h1>
                <p class="portal-lead">Deliver ShopLeap orders across the Philippines with your own riders and a portal that keeps every delivery in view.</p>
                <div class="portal-cta">
                    <a class="portal-button" href="{{ route('company.register.choose') }}">Create account <span aria-hidden="true">&rarr;</span></a>
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
                ['Register your company', 'Tell us about your business and upload your documents.'],
                ['Verify email', 'Enter the code sent to your email to submit your application.'],
                ['Get approved', 'Our team reviews your application and emails the decision.'],
                ['Add riders and start delivering', 'Build your rider team and manage orders in your portal.'],
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
                    ['Manage your own riders', 'Review rider applications and organize your delivery team.'],
                    ['Track deliveries', 'Follow pickups, parcel movement and delivery progress in one place.'],
                    ['Simple onboarding', 'Register online, verify your email and follow a clear approval process.'],
                ] as [$heading, $copy])
                    <article class="portal-card"><h3>{{ $heading }}</h3><p>{{ $copy }}</p></article>
                @endforeach
            </div>
        </div>
    </section>
    <section id="coverage" class="portal-section portal-container">
        <p class="portal-eyebrow">Local knowledge. Nationwide ambition.</p><h2>Choose the regions you serve.</h2>
        <p class="portal-copy">Select your service regions during registration, then configure your hub's detailed service areas after approval. These are the same regions available in your application.</p>
        <ul class="portal-regions" aria-label="Service regions">@foreach (config('logistics.regions') as $region)<li>{{ $region }}</li>@endforeach</ul>
    </section>
    <section id="requirements" class="portal-section portal-benefits">
        <div class="portal-container portal-two-column">
            <div><p class="portal-eyebrow">Ready when you are</p><h2>A few documents.<br>A strong start.</h2><p class="portal-copy">Have these documents ready for review. Each file must be JPG, PNG or PDF, up to 5 MB.</p></div>
            <ul class="portal-document-list">
                <li>SEC Certificate</li><li>BIR Form 2303</li><li>Mayor’s / Business Permit</li><li>Valid ID of the authorized representative</li>
            </ul>
        </div>
    </section>
    <section id="faq" class="portal-section portal-container">
        <p class="portal-eyebrow">Before you hit the road</p><h2>Your questions, answered.</h2>
        <div class="portal-faq">
            @foreach ([
                ['How long does approval take?', 'Our team reviews your documents after email verification. Timing depends on the application and any clarifications needed. We will email you when a decision is made.'],
                ['Who can register?', 'An authorized representative of a logistics company with the required business documents can apply.'],
                ['How are riders added?', 'Riders use the ShopLeap Rider app to apply to your sorting center. Once your company is approved, you can review and approve their applications in the portal.'],
                ['Which documents do I need?', 'An SEC Certificate, BIR Form 2303, Mayor’s or Business Permit, and a valid ID of the authorized representative. Upload JPG, PNG or PDF files up to 5 MB each.'],
                ['How can I get support?', 'After signing in, use Help in your Logistics portal to contact the support team.'],
            ] as [$question, $answer])
                <details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
            @endforeach
        </div>
    </section>
    <section class="portal-final-cta portal-container"><div><h2>Your next chapter starts here.</h2><p>Put your delivery network to work with ShopLeap.</p></div><a class="portal-button" href="{{ route('company.register.choose') }}">Register your company &rarr;</a></section>
</x-logistics.public-layout>
