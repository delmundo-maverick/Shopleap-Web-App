# Navigation audit and handoff

## Before editing: audit findings

The audit inspected all role navbars, their consuming layouts / page headers, guest and auth headers, buyer checkout, policies, notification controls, app.js, navbar.js, buyer-header.js, buyerSearch.js, landing.js, app.css, navbar.css and Vite configuration. These are source-audit findings, not claims of pre-change browser testing.

| Navbar | Existing file / consumers | Mobile opening before refactor | Exact cause / change |
| --- | --- | --- | --- |
| Seller | resources/views/components/navbars/seller.blade.php; seller pages; notifications | Yes on data-sidebar-toggle pages; no on store-based triggers | Existing vanilla navbar.js listens only for data-sidebar-toggle. It never reads the sidebar Alpine store. Preserve slide-in drawer and desktop collapse, unify triggers, and add focus / Escape / scroll handling. |
| Logistics | resources/views/components/navbars/logistics.blade.php; components/logistics/layout.blade.php; riders/index; messages/index; help/index | No on shared-layout, riders and messages; yes on help's data trigger | @click sets $store.sidebar.open but the actual drawer only responds to vanilla class mutations. Both mechanisms replaced with one controller. |
| Admin | resources/views/components/navbars/admin.blade.php; dashboard, settings, transactions, logs, messages, registrations, returns, notifications | No on store-triggered registrations / returns / notifications; yes on data-triggered pages | Same disconnected store/vanilla mismatch. All triggers replaced with x-nav.toggle. |
| Buyer | resources/views/components/Buyer/header.blade.php; Buyer pages; notifications | No navigation menu | No hamburger or drawer. Profile and notification links sit in a row hidden below md. Add navigation drawer, mobile profile accordion, and accessible action controls. |
| Landing | resources/views/landing/index.blade.php; resources/js/landing.js | Yes below 640px | Own vanilla handler controls hidden class, with no Escape/outside/resize/scroll-lock support. Replace with common 1024px breakpoint and controller. |
| Auth | resources/views/auth/login.blade.php, register.blade.php, register/{buyer,seller,logistics}.blade.php, password-recovery.blade.php | Static headers, no drawer | No broken menu existed; markup was duplicated. Use common guest navigation. Recovery previously loaded only its page JS, now the shell supplies app.js too. |
| Policies | resources/views/policies/show.blade.php | Static links, no drawer | Replace repeated markup with guest shell preserving Terms and Privacy links. |
| Checkout | resources/views/Buyer/checkout/index.blade.php | Static header, no drawer | Use shared shell with secure-checkout brand and cart/account/home links. |
| Rider | API courier routes only | N/A | No rider web layout/navbar exists; web login explicitly directs couriers to the mobile app. |
| Generic layout | resources/views/layouts/app.blade.php | N/A | Empty, unused file; left unchanged. |

Alpine was already bundled using Vite: one import and one Alpine.start(), no Alpine CDN. Some layouts repeat the app.js tag, but the browser executes the same ES module once. Shared shells ensure that a consuming layout always includes the bundle. No second Alpine bootstrap was added. Existing notification factories duplicated inside Blade were consolidated into shared/notifications.js and registered before Alpine.start().

## Implemented structure

- Anonymous shared components: shell, link, dropdown, user-menu, mobile-menu, toggle, brand, workspace, guest and notifications.
- Role sidebars retain their existing links, groups, icons, query parameters, pending-return counts and unread-message polling. Seller order links preserve distinct new/prepare active states. Active routes use request()->routeIs().
- Buyer retains location updates, search/recent/trending controls, cart count and preview, wishlist store/count, notifications feed/mark-read/mark-all, profile/purchases/vouchers/messages links, and logout.
- Landing retains its configured navigation anchors, trending searches, seller/rider entry points and login/register links. Auth-page anchors point back to the actual landing sections.
- One navbar controller owns mobile open state, one active dropdown, keyboard movement, focus trapping/return, desktop collapse persistence, logout confirmation and scroll locking. Link, Escape, overlay/outside click, navigation and desktop resize close mobile menus.
- x-cloak prevents open flashes. Native buttons and links have visible focus indicators and minimum 40px targets. Mobile panels use 100dvh and safe-area padding.
- Semantic navbar variables live in app.css, built from the existing palette. navbar.css documents the layering: page headers <=30; nav 32; overlay 40; drawer 45; dropdown 48; existing page modals >=50; logout 90. Top bars stay in normal layout flow and are sticky. Sidebars occupy desktop layout width.
- Vite config did not need changes: it already discovers all resources/css and resources/js files.

## Deleted files

- resources/js/shared/navbar.js (replaced vanilla drawer/collapse/logout/poll handlers).

The duplicated navbar/header HTML and old landing initMobileMenu function were removed from their original files. navbar.css was repurposed for shared components, not deleted.

## Commands

Already run successfully:

```powershell
npm run build
php artisan view:clear
php vendor/bin/pint --dirty --format agent
```

Run npm run build and php artisan view:clear again when deploying or copying these source changes to another environment. If developing with Vite, npm run dev remains the normal development option. No dependency or database migration is required for this navbar refactor.

## Verification performed

- 72 scoped PHP tests passed (498 assertions), including component rendering for seller, logistics, admin and buyer, route resolution, unique IDs, retained links/badges/CSRF, seller active-tab behavior, guest/login rendering and single Alpine bootstrap.
- 9 navigation JavaScript state tests passed: mobile state at widths 320, 375, 414, 768 and 844; desktop resize at 1024; scroll restoration; Escape; one dropdown; link closure; logout confirmation.
- Production Vite build passed; Blade views compiled and were subsequently cleared; Pint and git diff --check passed.

**Not verified in a real browser:** actual viewport rendering, horizontal overflow, overlaps, clipped panels, touch behavior, and browser-console errors. Computer-use inventory returned no available browsers; attempting to open the in-app browser returned 'Browser is not available: iab'. The width tests above exercise state, not browser layout. Do not treat them as visual verification.

## Manual checklist (pending browser access)

For EACH row below, repeat the common checks at **320, 375, 414, 768 and 1024px**, plus phone landscape (for example 844x390). At 1024px the mobile toggle must disappear, any open drawer/overlay must close, and the desktop navigation must appear. Also resize back below 1024px.

| Role / header | Pages and preserved features to inspect |
| --- | --- |
| Seller | Dashboard; inventory; new vs prepare orders; returns badge; vouchers; reports; messaging unread badge; account; desktop collapse/reload persistence. |
| Buyer | Home; My Account; location/empty address; purchases/tracking; wishlist; cart count/preview; search/recent/trending; vouchers; notification feed/mark-all; messages; profile menu. |
| Logistics | Dashboard; shared-layout pages (inbound/outbound/sorting/deliveries/reports); riders; messages; help; session history; unread badge; desktop collapse. |
| Admin | Dashboard; all three registrations pages; returns badge; transactions; logs; settings subpages; messages; notifications page; desktop collapse. |
| Guest / auth / policy | Landing links and search, login, role-registration forms, password recovery, Terms and Privacy. Confirm the correct return-to-landing anchors and seller/rider/login/register entry points. |
| Checkout | Home/cart/account links, secure-checkout label, address controls and checkout state unaffected by opening/closing the navigation. |
| Rider | N/A: no rider web navbar. |

Common checks for every applicable role:

- [ ] Hamburger opens AND closes the menu; aria-expanded and its accessible label update.
- [ ] A link closes the menu and reaches the expected named route or section.
- [ ] Escape closes the active dropdown first, then the drawer; focus returns to its trigger.
- [ ] Clicking the overlay/outside closes the menu; tapping inside does not close it unexpectedly.
- [ ] Resizing past 1024 closes the menu and restores scrolling; rotating does not strand the overlay.
- [ ] Background scrolling is locked while the drawer/logout dialog is open and restored after closing.
- [ ] Dropdowns/accordions open by Enter/Space; ArrowDown/ArrowUp move among links; only one navbar dropdown stays open.
- [ ] Tab/Shift+Tab stay inside an open drawer/logout dialog, with visible focus.
- [ ] Current-route highlighting is correct, including seller new/prepare and individual admin settings sections.
- [ ] Logout includes CSRF, Cancel preserves the session, and confirming logs out successfully. Guest headers have no logout.
- [ ] No horizontal scrolling, overlapping controls, clipped dropdowns or content hidden under sticky bars. Check long account/shop names and nonzero badges.
- [ ] Panels respect safe areas and stay below page modals. Open a business modal after closing navigation.
- [ ] Browser console has no Alpine undefined-method/state errors, duplicate initialization errors or failed navbar module requests.

## Full changed files

Complete, untruncated source is in navigation-full-files.txt. It includes all files changed by this navbar refactor, including consuming page headers/layouts and automated tests. Existing unrelated edits elsewhere in the working tree are not included as separate task changes.

- `resources/css/app.css`
- `resources/css/navbar.css`
- `resources/js/app.js`
- `resources/js/landing.js`
- `resources/js/shared/navigation.js`
- `resources/js/shared/notifications.js`
- `resources/views/Buyer/checkout/index.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/logs/index.blade.php`
- `resources/views/admin/messages/index.blade.php`
- `resources/views/admin/registrations/buyers/index.blade.php`
- `resources/views/admin/registrations/logistics/index.blade.php`
- `resources/views/admin/registrations/sellers/index.blade.php`
- `resources/views/admin/returns/index.blade.php`
- `resources/views/admin/settings/layout.blade.php`
- `resources/views/admin/transactions/index.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/password-recovery.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/register/buyer.blade.php`
- `resources/views/auth/register/logistics.blade.php`
- `resources/views/auth/register/seller.blade.php`
- `resources/views/components/Buyer/header.blade.php`
- `resources/views/components/logistics/layout.blade.php`
- `resources/views/components/nav/brand.blade.php`
- `resources/views/components/nav/dropdown.blade.php`
- `resources/views/components/nav/guest.blade.php`
- `resources/views/components/nav/link.blade.php`
- `resources/views/components/nav/mobile-menu.blade.php`
- `resources/views/components/nav/notifications.blade.php`
- `resources/views/components/nav/shell.blade.php`
- `resources/views/components/nav/toggle.blade.php`
- `resources/views/components/nav/user-menu.blade.php`
- `resources/views/components/nav/workspace.blade.php`
- `resources/views/components/navbars/admin.blade.php`
- `resources/views/components/navbars/logistics.blade.php`
- `resources/views/components/navbars/seller.blade.php`
- `resources/views/components/notification-bell-script.blade.php`
- `resources/views/components/notification-bell.blade.php`
- `resources/views/landing/index.blade.php`
- `resources/views/logistics/help/index.blade.php`
- `resources/views/logistics/messages/index.blade.php`
- `resources/views/logistics/riders/index.blade.php`
- `resources/views/notifications/index.blade.php`
- `resources/views/policies/show.blade.php`
- `resources/views/seller/account/index.blade.php`
- `resources/views/seller/dashboard.blade.php`
- `resources/views/seller/help/index.blade.php`
- `resources/views/seller/messages/index.blade.php`
- `resources/views/seller/orders/index.blade.php`
- `resources/views/seller/products/create.blade.php`
- `resources/views/seller/products/discount.blade.php`
- `resources/views/seller/products/index.blade.php`
- `resources/views/seller/reports/index.blade.php`
- `resources/views/seller/returns/index.blade.php`
- `resources/views/seller/reviews/index.blade.php`
- `resources/views/seller/vouchers/create.blade.php`
- `resources/views/seller/vouchers/edit.blade.php`
- `resources/views/seller/vouchers/index.blade.php`
- `resources/views/seller/vouchers/show.blade.php`
- `tests/Feature/NavigationTest.php`
- `tests/Unit/NavigationState.test.js`
