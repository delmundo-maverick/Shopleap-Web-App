# Landing link audit

Baseline link occurrences (in source order):

```blade
<link rel="icon" type="image/png" href="{{ asset('images/shopleap-logo.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap"
<a href="{{ $resolveTarget($slide['cta_target']) }}"
<a href="#deals"
<a href="{{ route('register') }}"
<a href="{{ route('register') }}"
<a href="{{ route('register') }}"
<a href="{{ route('register') }}"
<a href="{{ route('register') }}"
<a href="{{ route('register.seller') }}"
<a href="{{ $riderRegisterUrl }}"
<a href="{{ $riderRegisterUrl }}"
<a href="{{ route('register.seller') }}"
<a href="{{ $riderRegisterUrl }}"
<a href="{{ route('register') }}"
<li><a href="{{ route('register') }}"
<li><a href="#" class="hover:text-white">Help Center</a></li>
<li><a href="#" class="hover:text-white">How to Buy</a></li>
<li><a href="#" class="hover:text-white">Returns & Refunds</a></li>
<li><a href="#" class="hover:text-white">Contact Us</a></li>
<li><a href="#about" class="hover:text-white">About Us</a></li>
<li><a href="{{ route('register.seller') }}" class="hover:text-white">Sell on Shopleap</a>
<li><a href="{{ $riderRegisterUrl }}" class="hover:text-white">Ride with Shopleap</a></li>
<li><a href="{{ route('terms') }}" class="hover:text-white">Terms & Conditions</a></li>
<li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a></li>
```

## Status and disposition of every link family

| Original link / location | Original status | Final disposition |
| --- | --- | --- |
| Favicon | Existing local PNG | Wired to consistently cased Shopleap-Logo.png |
| Google font preconnects and Inter/Sora stylesheet | Additional landing-only font loads | Removed; existing app.css fonts reused |
| Shared header logo | Real home route | Retained |
| Header Home / Categories / Deals / How it works anchors | Existing section anchors | Retained with updated labels and matching IDs |
| Header Riders anchor | Recruitment | Removed |
| Header About anchor | Old marketing section | Removed with section |
| Header Sell on Shopleap | Real seller registration | Retained |
| Header Ride with Shopleap | Fallback to generic registration | Removed |
| Header Track My Order | Buyer-authenticated route | Removed from guest header; retained in buyer header |
| Header Help Center | Authenticated support route | Removed from guest header |
| Header Terms / Privacy | Real public policy routes | Retained |
| Header login | Real login route | Retained |
| Header registration | Generic role selector | Wired directly to register.buyer |
| Header search | Registration route, query discarded | Wired to home GET search; public active products only |
| Hero first CTA | Generic registration | Wired to products section |
| Hero sale CTA | Existing deals anchor, unsupported dated sale | Removed with carousel |
| Hero rider CTA | Registration fallback | Removed |
| Hero repeated Browse Deals CTAs | Existing deals anchor | Replaced by one Start shopping CTA |
| Categories See all | Generic registration | Removed redundant link |
| Every category tile | Generic registration | Wired to home category filter and discover results anchor |
| Deals See all | Generic registration | Removed; limited public lists shown |
| Every deal/product card | Generic registration | Wired to existing buyer.products.show; guest login required by existing route |
| How it works buyer CTA | Generic registration | Replaced by factual browse/order/track instructions |
| How it works seller CTA | Real seller registration | Retained in hero, seller banner and footer |
| How it works ride CTA | Registration fallback | Removed |
| Rider section CTA | Registration fallback | Removed with section |
| Additional seller promotional CTA | Real seller registration | Consolidated into seller banner |
| Additional rider promotional CTA | Registration fallback | Removed |
| Every old footer category and subcategory link | Generic registration | Removed redundant sitemap; category tiles provide filtering |
| Footer Help Center | href="#" | Removed; no public help destination identified |
| Footer How to Buy | href="#" | Replaced by How it works section; removed footer placeholder |
| Footer Returns & Refunds | href="#" | Removed; no public returns information route identified |
| Footer Contact Us | href="#" | Removed; existing messaging requires authentication |
| Footer About Us | Old about anchor | Removed with old section |
| Footer Sell on Shopleap | Real seller registration | Retained |
| Footer Ride with Shopleap | Registration fallback | Removed |
| Footer Terms and Conditions / Privacy Policy | Real public routes | Retained |
| New logistics partner links | Real public register.logistics | Added in header/menu/footer |
| New guest save action | Login required | Login with status message; server stores home URL with search/category filters |
| New Clear filters | Real home route and products anchor | Clears search and category |

Product and shop detail routes currently remain behind auth + buyer middleware. Public listing browsing works; detail browsing requires login. These access rules were preserved. Guest landing exposes no cart, purchase, follow, claim, or message controls. No featured shops or vouchers exist on buyer home, so neither was added. No personal account data or claimed vouchers is displayed.

## Removed files and content

Deleted resources/js/landing.js, public/images/rider-hero.png, public/images/hero/Landing-1.png, public/images/hero/Landing-2.png and public/images/hero/Landing-3.png. The first image was converted to public/images/hero/landing-shopping.webp before removal. Source-reference searches found no remaining runtime users of the removed assets.

Replaced obsolete landing CSS and config; removed carousel/dots, reveal observers, fake trending terms, recruitment, dated promotions, unsupported payment/partner claims, stats queries, old FAQ/about blocks and duplicated landing font loads. Shared Alpine bootstrap remains in app.js. No dependencies changed. Existing app.css tokens are reused without adding palette values.

## Verification

- Landing/navigation feature tests: 18 tests, 122 assertions (see final run).
- Production Vite build: passed. Existing optional fontaine warning remains; no dependency added.
- Blade compilation and Pint: passed.
- Browser inventory returned no browsers/apps. Responsive visual checks, console inspection, and Lighthouse could not run. LCP: unmeasured. CLS: unmeasured.
- No Boost MCP tools are exposed in this session, so CLI/source inspection was used. Cache API checked against Laravel 13 documentation: https://api.laravel.com/docs/13.x/Illuminate/Support/Facades/Cache.html
- No full authenticated login transaction was performed; tests verify intended-session destinations and existing login code consumes redirect()->intended().

## Commands

```powershell
npm run build
php artisan view:clear
php artisan route:clear
```

No migration or dependency installation is required. If Vite is running, it serves current sources; otherwise stop the dev server normally so public/hot does not point to a stale server.

## Manual checklist (pending browser verification)

- At 320, 375, 414, 768, 1024 and 1440 px: verify no page overflow, readable cards, hero scales below copy on mobile, and all buttons have comfortable targets.
- At each width, verify menu closed/open; toggle, link click, Escape, outside click, resize to desktop, focus return and body scroll unlock. Confirm x-cloak prevents flashing.
- Verify logo/home, search submit, each category, Start shopping, seller hero CTA, product cards, Clear filters, seller banner, all footer policy/registration links, header Login/Register and logistics links.
- Verify guest product-card heart opens login with the message, then buyer login returns to the same filtered landing URL. Verify direct protected product/shop navigation returns to that detail URL after login.
- Check buyer header retains cart preview/count, location, notifications, recent search, wishlist, account links, messages and logout on desktop/mobile.
- Verify featured products and discover sections with normal data and with no active products; verify no categories configured. Shops have no landing section, so an empty shops database must not affect rendering.
- Verify live discount prices and variant-only thumbnails; watch for extra per-card queries on cold and warm cache requests. Product list updates may take 60 seconds.
- Verify one h1, heading order, accessible search/button names, hero alt text, title, description, canonical and Open Graph tags.
- Inspect browser console and network errors. Run Lighthouse mobile against the production build and record LCP/CLS; neither was measured here.

Navigation JavaScript state tests: 12 passed using node --test --test-isolation=none tests/Unit/NavigationState.test.js. These simulate menu state and do not replace browser visual checks.


## Follow-up: category-first landing (supersedes hero details above)

Hero markup, Start shopping CTA, hero-only WebP and hero-only styles are removed. Home still targets main#home; Categories, Products, Discover and How it works anchors remain live. Title and Open Graph text now describe category/product browsing. Categories is the page h1.

All 14 existing public/images/categories PNGs are present in the tiles. Data still comes from shopleap_categories.list; image filenames match the original landing category assets. Images have 96x96 dimensions, contained frames and async decoding. The first six load eagerly (covering the widest first row); the remainder load lazily. No category asset was deleted or replaced.

The shared .site-container aligns content, guest/buyer header rows and landing footer at 1600px maximum width with 16/24/32px gutters. Categories/products share a 2/3/4/5/6-column grid and 12px gaps. All colors use existing app.css tokens.

Landing x-reveal registers before the single Alpine.start() through app.js. A single IntersectionObserver reveals below-fold elements once with a 20px translation, 300ms easing and 60ms per-column stagger. Initially visible elements skip animation. Reduced motion, missing observer support, observer setup failure and keyboard focus keep content accessible. will-change applies during the reveal only, then is removed. Hover lift/zoom and the scrolled sticky header shadow use existing palette tokens.

Validation: 19 feature tests (159 assertions), 21 JavaScript state tests, Vite production build, Pint and Blade compilation passed. Browser inventory is empty. Visual checks at 320/768/1024/1440/1920, image sharpness, console and Lighthouse LCP/CLS comparisons remain pending; simulated viewport tests check animation state only.

Manual follow-up: confirm Categories begins immediately under header with no hero gap; all 14 images remain sharp and uncropped; header/content/footer edges align; category links filter products; scroll reveals run once; first viewport appears immediately; keyboard focus reveals pending cards; reduced motion disables movement; mobile menu works; no horizontal scrollbar; compare production Lighthouse LCP/CLS with baseline.
