# Production readiness audit

Reviewed 2026-11-14 (Asia/Manila).

Verdict: **NOT READY**.

This audit covers the existing working tree, including substantial changes that predated this request. No dependency versions, business calculations, status transitions, vouchers, shipping rules, earnings rules, production records, or test files were changed. No production migrations, seeders, scheduler tasks, or outgoing mail were executed. Checks used static inspection, Artisan, the installed Laravel 13.26.1 source, existing tests, package audits, and the production asset build. The report is not a claim that every rendered page or live infrastructure setting has been verified.

## Release blockers

1. Credentials were present in `.env.example`. The current template is sanitized, but exposed R2 and SMTP credentials must be revoked/replaced. Determine whether the exposed application key was deployed; replacement needs a plan for encrypted data, sessions and cookies. Git history and existing deployment images also need review.
2. Composer reports 18 advisories across 9 installed packages. Approve and validate dependency updates, or document applicability and explicit risk acceptance. Advisory severity is package-level evidence; exploitability in this application has not been established.
3. The full existing PHP suite is not green: 295 tests, 229 passed, 1 assertion failure, 65 errors, 1,500 assertions. Most errors involve custom fixture schemas missing public order fields or missing tables. Test changes were prohibited, so these remain unresolved.
4. Render scheduler operation has not been demonstrated. The application definitions and missing command discovery are repaired, but the supplied web container starts only PHP-FPM and Nginx. Six business-critical tasks need one scheduler service.
5. Tracked database and diagnostic dumps require sensitivity/retention review. They are excluded from new Docker builds, but remain in the repository and its history.
6. Live browser, role-by-role mobile, production PostgreSQL migration, real mail/R2, and built Docker image checks remain outstanding. Static checks and SQLite tests cannot establish these properties.

## Issue table

Locations refer to the resulting working tree; deleted paths and earlier locations are labeled. Dependency advisories are listed individually below.

| Phase | File and line | What was wrong | Severity | Fixed? |
| --- | --- | --- | --- | --- |
| 2 | `resources/views/Buyer/shop/show.blade.php:169` | Shop Home tab used a bare `#`. | minor | Yes: named shop route. |
| 2 | `resources/views/components/footer.blade.php:8`; `resources/views/components/nav/guest.blade.php:6` | Same-host policy links fail on a separately hosted admin portal, whose host middleware disallows policy paths. | important | Yes: links use the configured main portal. |
| 2 | `resources/views/errors/layout.blade.php:1` and status views | No application-designed 403, 404, 419, 429, 500 or 503 pages. | important | Yes: six branded, database-independent views; rendered successfully with production/debug-off settings. |
| 2/6 | `routes/web.php` (former lines 156–157) | Unreferenced pending-registration route targeted a nonexistent view. | important | Yes: removed the route; current registration flows do not call it. |
| 3 | `resources/js/pages/admin/transactions.js:1`; `resources/js/pages/seller/reports.js:1` | Report charts depended on an external Chart.js script although Chart.js is already installed locally. | important | Yes: bundled installed Chart.js; removed the two CDN tags. |
| 3 | `resources/views/components/nav/user-menu.blade.php:12` | A failed sidebar avatar URL left a broken image without initials. | minor | Yes: initials remain underneath the image, following the existing avatar fallback convention. |
| 3 | `resources/views/components/Buyer/product-card.blade.php:24` | Shared catalog card fallback appeared only for an absent URL, not a failed image request. | minor | Yes: placeholder remains underneath the image and appears if loading fails. |
| 3 | `resources/js/pages/seller/voucher-create.js:1`; `resources/views/seller/vouchers/create.blade.php:9` | Inline voucher generator had no error handling or request/loading guard. | important | Yes: extracted handler, timeout, disabled state, accessible failure message. Voucher rules are unchanged. |
| 3 | `resources/js/components/announcement-banner.js:1`; corresponding component | Inline announcement dismissal threw when localStorage was unavailable. | minor | Yes: extracted handler and storage failure handling. |
| 3 | `resources/views/components/nav/workspace.blade.php:30`; buyer cart/catalog/shop and auth page footers | Policy access was missing from workspace navigation and several standalone pages. | minor | Yes: shared links/footer added. Printed labels, PDFs and email bodies were not given application footers. |
| 3 | `resources/views/seller/orders/index.blade.php:155`; `resources/views/components/product-status.blade.php:1`; `resources/views/seller/products/discounts.blade.php:71` | Status display is not consistently routed through `x-status-label`; product tooltips and discount labels have separate semantics. | minor | No: coordinated component consolidation needs label/tooltip parity review. |
| 3 | `resources/views/seller/vouchers/create.blade.php:44`; `resources/views/auth/choose-role.blade.php:19` | Some traditional forms lack an explicit submit-loading/double-click state; existing protection is not universal. | important | No: full form-level acceptance remains open. Do not globally disable named submit buttons without preserving their submitted values. |
| 3 | `resources/js/pages/admin/transactions.js:11`; `resources/js/pages/seller/reports.js:10` and other existing files | Some chart colors remain hardcoded outside the shared CSS theme. | minor | No: visual consolidation retained for review rather than rewriting working displays. |
| 3 | All rendered role pages | Browser console, mobile overflow, dropdown stacking, real image failures and every empty/validation/toast state were not exhaustively exercised. | important | Unverified: release acceptance required. |
| 4 | `.env.example:4`, `:48`, `:49`, `:59` | Template contained application/R2/SMTP credential values. | blocker | Template fixed; revocation, key-impact assessment and historical remediation remain open. No values are reproduced here. |
| 4 | `.dockerignore:13` | Database/diagnostic dumps and generated bootstrap/view caches could be copied into production images. | important | Yes: excluded these paths and local audit/rule material. Repository retention remains separate. |
| 4 | `routes/console.php:18`; `routes/web.php` (former schedule section) | All six schedule definitions disappeared with route caching. | blocker | Yes: definitions moved unchanged to console routes; six tasks verified with route cache enabled. |
| 4 | `app/Console/Commands/AutoApproveCancellationRequests.php:1`, `AutoCompleteDeliveredOrders.php:1`, `ProcessReturnDeadlines.php:1` | These classes were under `app/Commands` despite their `App\Console\Commands` namespace; Artisan did not discover them. | blocker | Yes: relocated to the namespace-matching directory. No business logic changed. |
| 4 | `config/sanctum.php:53` | Documented `SANCTUM_EXPIRATION` did not affect token lifetime. | important | Yes: optional integer setting is now read; empty retains unlimited global lifetime. |
| 4 | `.env.example:60` | SMTP template used `MAIL_ENCRYPTION`, which the installed mail configuration does not read. | minor | Yes: uses `MAIL_SCHEME=smtp`; provider-specific TLS/SMTP settings must be verified. |
| 4 | `docker/php.ini:1` | PHP limited individual uploads to 12 MB while application video validation permits 20 MB. | important | Yes: aligned PHP to 20 MB. |
| 4 | `docker/supervisord.conf:1` | Web image has no scheduler process. | blocker | No infrastructure changed: run exactly one dedicated scheduler service as described below. |
| 4 | `vite.config.js:25` | Build reports an optional fontaine optimization warning. | minor | No: assets build successfully; dependency addition is not authorized and is not required for correctness. |
| 4 | `Dockerfile:29` | Docker PHP 8.4 differs from local PHP 8.5.9; container runtime parity has not been tested. | important | No: supported package constraints alone do not replace a container smoke test. |
| 4/5 | `config/mail.php:17`, `:89`; `app/Services/OtpService.php:66` | Production log/failover mail can expose OTP bodies; exception logs may contain SQL bindings or provider details. | important | Deployment decision: use a delivery mailer, avoid log fallback, review log access/redaction/retention. No direct application password/OTP logging was found. |
| 5 | `database/seeders/DatabaseSeeder.php:18`; `database/seeders/AdminSeeder.php:13` | Development account seeders could run with production `--force`, including a known default administrator password. | important | Yes: both refuse production execution. Existing accounts and the historical password still require review; do not use these seeders for production provisioning. |
| 5 | `config/support.php:5`; `app/Http/Controllers/MessagingController.php:511` | New chat files use private storage, but legacy public-bucket attachments can still be served by public URLs. | important | No: inventory/migrate legacy objects and revoke public access after a retention/access decision. An authorized redirect does not make an already-public object private. |
| 5 | `bootstrap/app.php:25` | All proxy addresses are trusted, including forwarded host headers. Render edge header rewriting/isolation has not been verified. | important | No: confirm edge behavior or approve explicit proxy/header restrictions for the actual infrastructure. |
| 6 | `resources/views/auth/Concerns/HandlesOtpRegistration.php` (deleted) | Unreferenced PHP trait copy under the view directory; the canonical autoloaded controller trait already exists. | minor | Yes: removed only the misplaced copy. |
| 6 | `app/Models/OrderCancellation.php` (deleted) | Duplicate `App\Services\OrderCancellation` declaration in a path violating PSR-4; canonical service exists under `app/Services`. | important | Yes: removed the unreferenced misplaced duplicate; retained canonical service and its behavior. |
| 6 | `app/Services/SellerReportService.php:16`, `:137`; `app/Services/AdminTransactionService.php:16`; `app/Services/LogisticsReportService.php:187` | Report builders/exports materialize complete selected date ranges; large ranges can exhaust memory. | important | No: financial query/aggregation changes require separate review. |
| 6 | `app/Http/Controllers/Admin/TransactionController.php:84` | CSV export uses `cursor()` then reads seller/profile relations. Cursor iteration does not apply the query's eager loading, allowing per-order queries. | important | No: approve batched eager-loaded export iteration and verify output parity. |
| 6 | `phpinfo_dump.txt:1`; `shopleap.dump:1` | Tracked diagnostic/database artifacts have no runtime references; database archive includes sensitive schema field names and may include personal data. | blocker | Excluded from new images; retained for owner sensitivity/retention decision. Archive was not restored or its records printed. |
| 6 | `resources/views/layouts/app.blade.php:1`; `resources/views/components/nav/notifications.blade.php:1` | No literal source references found; dynamic/external use is not ruled out. | minor | Retained as uncertain candidates. |
| Verification | `tests/Feature/ExampleTest.php:3`; affected fixture suites listed below | Existing full suite fails; fixture drift and isolation prevent a reliable green regression baseline. | blocker | No test files changed, per instruction. |

## Needs my decision

- Approve dependency upgrades after reviewing the advisory list below. No `composer update`, `npm install`, package removals or lockfile edits were performed.
- Revoke exposed mail/R2 credentials; assess the application key and any deployed default administrator account. Plan encrypted-data compatibility before replacing an application key.
- Decide retention and secure archival/removal of tracked dumps, and whether repository-history remediation is needed. New Docker exclusions do not erase prior images or Git history.
- Permit maintenance of stale test fixtures, or supply an alternative green regression gate. Application code was not weakened to accommodate incomplete test schemas.
- Configure one Render scheduler service. Choose runtime image parity and demonstrate migration/startup behavior against a safe PostgreSQL staging copy.
- Approve a separate performance change for financial report aggregation/export batching and any measured database index changes. No speculative migrations were created without live schema/query-plan evidence.
- Review legacy public chat attachments and approve their migration/access policy.
- Complete browser acceptance for every role and form, including loading/validation/empty/toast states, all mobile navigation, modals, status-component parity and chart theme consolidation.
- Confirm trusted-proxy boundary, actual secrets, bucket permissions, sender deliverability and log retention/redaction in Render. These are not established by repository settings alone.

## Production configuration and deploy sequence

Use a clean release checkout that includes the new/moved files in this working tree. Preserve a database backup and verify the migrations against PostgreSQL staging first. Do not run `migrate:fresh`, `migrate:refresh`, `db:seed` or the development seeders in production. The commands below are the deployment sequence to run after the release blockers are resolved; database-mutating commands were not run during this audit.

Configure these Render variables before starting the release:

```dotenv
APP_NAME=Shopleap
APP_ENV=production
APP_DEBUG=false
APP_KEY=<stable-secret-application-key>
APP_URL=https://<main-domain>
MAIN_BASE_URL=https://<main-domain>
LOGISTICS_BASE_URL=https://<logistics-domain>
ADMIN_BASE_URL=https://<admin-domain-or-main-domain>
APP_TIMEZONE=Asia/Manila
PORTAL_FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
CACHE_LIMITER_STORE=database
PSGC_CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=pgsql
DATABASE_URL=<secret-postgresql-uri-with-url-encoded-password>
DB_SSLMODE=require
FILESYSTEM_DISK=r2_public
R2_ACCESS_KEY_ID=<new-scoped-key>
R2_SECRET_ACCESS_KEY=<new-secret>
R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
R2_PUBLIC_BUCKET=<public-bucket>
R2_PRIVATE_BUCKET=<private-bucket>
R2_PUBLIC_URL=https://<public-asset-domain>
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=<provider-host>
MAIL_PORT=587
MAIL_USERNAME=<provider-username>
MAIL_PASSWORD=<new-provider-secret>
MAIL_FROM_ADDRESS=<verified-sender>
MAIL_FROM_NAME=Shopleap
SANCTUM_STATEFUL_DOMAINS=<main-host>,<logistics-host>,<admin-host>
```

Alternatively supply DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD and leave DATABASE_URL unset. The URL overrides those fields. Keep portal session cookies host-only: this application intentionally fixes `session.domain` to null; do not introduce a shared SESSION_DOMAIN. API authentication currently uses courier bearer tokens, not an enabled stateful SPA middleware stack; merely listing Sanctum stateful hosts does not enable cookie-based cross-domain API authentication. Existing CORS permits configured portal origins and does not enable credentials.

Optional: LOGISTICS_SUPPORT_EMAIL, ADDITIONAL_ALLOWED_HOSTS (explicit hostnames, including the Render hostname if it must serve application traffic), SANCTUM_EXPIRATION (a positive minute count if global token expiry is wanted), SESSION_COOKIE, MAIL_EHLO_DOMAIN, MAIL_URL. For Resend instead of SMTP, set MAIL_MAILER=resend, RESEND_API_KEY and a verified MAIL_FROM_ADDRESS; SMTP credentials are then unnecessary. The supplied Docker image has no Redis extension/client configured for use here; database-backed cache/session/limiter storage is the supported shared-store choice for this release. Adding Redis requires its own dependency/runtime setup.

For a new environment generate APP_KEY once using `php artisan key:generate --show --no-interaction` in a secure console and save its output to Render's secret settings. Never regenerate it at each deploy. For this repository's exposed key, resolve the compatibility/rotation decision first.

Build stage (already represented by Dockerfile stages):

```sh
npm ci
npm run build
composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction
```

Application preparation, in order (the existing `docker/prepare-deploy.sh` runs this sequence):

```sh
php artisan config:clear --no-interaction
php artisan migrate --force --isolated=1 --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan schedule:list --no-interaction
```

Check migration output and `php artisan migrate:status --no-interaction` before considering the release healthy; an isolated migration invocation skipped because another process holds the lock is not proof this process completed migrations. Prefer one controlled release/pre-deploy migration execution rather than racing multiple replicas through schema changes. Config caching is performed with runtime Render values, not during image build. Start the web image with its existing `/entrypoint.sh`; it prepares Laravel, sets writable-directory ownership and starts Supervisor's PHP-FPM/Nginx services. Health endpoint is `/up`; its host/HTTPS bypass is intentional for platform health checks. No local storage link is needed for R2; configure one only if local public disk uploads are deliberately enabled.

Scheduler: create exactly one long-running service using the same release image/environment and override its Docker command with:

```sh
sh -c 'php artisan config:clear --no-interaction && php artisan config:cache --no-interaction && php artisan route:cache --no-interaction && php artisan schedule:work --no-interaction'
```

Start it only after successful release migrations. The scheduler command override bypasses the default web CMD; do not start the normal Nginx entrypoint for a scheduler service. Alternatively invoke `php artisan schedule:run --no-interaction` every minute with a platform scheduler able to reach the same application environment. Do not use both approaches. The current task definitions have no overlap/multi-server locks, so enforce one scheduler instance and monitor runtimes before scaling scheduler execution.

| Scheduled command | Timing in APP_TIMEZONE | Purpose |
| --- | --- | --- |
| `orders:auto-complete` | 02:00 daily | Complete eligible delivered orders using existing rules. |
| `orders:auto-approve-cancellations` | Hourly | Approve eligible unanswered requests after the existing 24-hour threshold. |
| `returns:process-deadlines` | Hourly | Process existing return/refund deadlines. |
| `sorting:close-stale-sessions` | Hourly | Close sessions after the existing inactivity threshold. |
| `bags:flag-stale-in-transit` | Hourly | Flag stale transfers. |
| `parcels:flag-stale-events` | Hourly | Flag parcels without recent tracking. |

No application job classes or ShouldQueue implementations were found. Mailables are PasswordChanged and PasswordResetOtp. Notifications are ActivityNotification, LogisticsDecisionNotification, OtpNotification and RegistrationDecisionNotification. They currently execute synchronously; the Queueable trait alone does not make them queued. OTP delivery failures are handled synchronously and must remain visible to the caller. A worker is not currently needed for these classes, and changing QUEUE_CONNECTION alone will not enqueue them. If future queued work is approved, use a dedicated service running `php artisan queue:work --sleep=3 --tries=3 --timeout=60 --no-interaction`, configure its connection/failed-job storage, keep retry_after greater than timeout and restart workers on release. Do not add that worker as a substitute for the scheduler.

After deployment verify authentication and wrong-role behavior on all portals, actual policy publication, public-code order URLs/old printed-label scans, private-file authorization, public asset fallbacks, real OTP/recovery/decision mail links, all six task registrations, error pages, CORS, cookies, proxy redirects and application logs. Do not run business-mutating scheduled commands merely as smoke checks against production.

## Verification evidence and limitations

- `composer show --direct`: Laravel 13.26.1, Sanctum 4.3.3; package APIs checked against installed source where Boost search-docs was unavailable.
- `composer validate --strict`: passed. Optimized Composer autoload generation completed without duplicate-class warnings.
- PHP syntax lint: 313 files passed before removing the additional unused view-directory trait; no application PHP source changed afterward. Laravel Pint passed for dirty PHP files.
- `php artisan config:cache`, `route:cache`, `view:cache`: passed. Local config/route caches were cleared afterward to avoid leaving development processes pinned to an audit snapshot.
- `php artisan schedule:list` with cached routes: all six intended tasks present; command discovery verified without executing them.
- Six custom error views rendered with APP_ENV=production and APP_DEBUG=false. A production-environment route inventory contained no Debugbar or Boost browser-log routes, even with local development dependencies installed. Docker installs without dev dependencies.
- `npm run build`: passed after allowing Vite's required Windows child process outside the sandbox. Only the optional fontaine warning remains. No dependencies were installed or changed.
- `node --test --test-isolation=none tests/Unit/*.test.js`: 60 passed; deployment tests initially hit sandbox EPERM and passed when rerun outside it.
- Existing focused PHP checks (policies, public registration, recovery, request safeguards, logistics portal and decision mail): 85 passed, 616 assertions.
- Full suite: 295 tests; 229 passed, 1 failure, 65 errors. ExampleTest does not initialize the landing catalog tables. LogisticsDashboardTest, LogisticsReportsTest, RiderEarningsTest, SortingScannerTest and VoucherClaimsTest contain fixture schemas lacking public order columns. SellerFollowTest/ServiceAreaTest fail with missing tables during the combined run; fixture isolation needs review. NavigationTest supplies an unbound route fixture, producing “Route is not bound.” These diagnoses do not prove all remaining assertions would pass after fixture repair.
- `npm audit --json`: zero advisories reported. Composer's online audit findings are listed below; an initial sandbox network failure was retried successfully with network access.
- Static route-action scan: no missing application controller methods. Named-route candidates were manually checked to reject false positives from Request::route, Notification::route and Portal::route. Literal asset/component/include and POST-form CSRF scans found no additional confirmed missing targets. Blade compilation is syntax-level coverage, not proof of variables or data-dependent behavior on every page.
- Database indexes were reviewed in migration source: public order/waybill uniqueness, current/earning hub and courier indexes, parcel-event indexes, address ownership, voucher-claim uniqueness and PSGC lookup migrations already exist. Live PostgreSQL schema drift, query plans and migration execution remain unverified. No live schema/index mutations were performed.
- Main list queries generally paginate or explicitly limit their results. Performance exceptions and the CSV cursor issue are listed above; no before/after latency claim is made.
- No complete Git-history secret scan, real bucket permission test, deliverability test, exhaustive runtime role/ID matrix or browser accessibility/visual audit was performed. Unused candidates below are not deletion proof. Laravel Boost browser-logs, database-schema, get-absolute-url and record-rule tools were unavailable in this session; no shared rule was written through an alternative memory mechanism.

## Dependency advisory evidence

The following table is generated from the successful online Composer audit. No dependency update has been applied. Every row remains open under Phase 4/5 at its corresponding `composer.lock` package entry. Treat the unresolved dependency risk as a release gate; a package advisory does not by itself demonstrate application reachability.

| Phase | File/line | Package / installed version | Advisory | Reported severity | Audit severity | Fixed? |
| --- | --- | --- | --- | --- | --- | --- |
| 4/5 | `composer.lock:1546` | laravel/framework v13.26.1 | [Laravel: Task Output Injection - Concurrency Process](https://github.com/advisories/GHSA-63hm-g96r-2m2q) (PKSA-yxbx-3c9s-cjt9) | low | important | No; approval required |
| 4/5 | `composer.lock:1546` | laravel/framework v13.26.1 | [Laravel TokenGuard authentication bypass through non-string API credentials](https://github.com/advisories/GHSA-f9x3-7vq9-cph6) (PKSA-b41t-wbzk-9q51) | critical | blocker | No; approval required |
| 4/5 | `composer.lock:1546` | laravel/framework v13.26.1 | [Laravel relationship inclusion can trigger unauthorized data destruction](https://github.com/advisories/GHSA-5wcm-3v2w-8362) (PKSA-4hy6-m98j-dz9f) | medium | important | No; approval required |
| 4/5 | `composer.lock:1546` | laravel/framework v13.26.1 | [Laravel: XSS in Debug Page Information](https://github.com/advisories/GHSA-jh5r-qr3c-85q8) (PKSA-d5tc-s1qs-h781) | low | important | No; approval required |
| 4/5 | `composer.lock:2025` | league/commonmark 2.10.0 | [league/commonmark: DisallowedRawHtml bypassed when a disallowed tag name ends the raw-HTML literal](https://github.com/advisories/GHSA-97jj-33gv-5xf9) (PKSA-m2dq-1fhr-29b1) | medium | important | No; approval required |
| 4/5 | `composer.lock:2025` | league/commonmark 2.10.0 | [league/commonmark: Quadratic-time denial of service in the GitHub Flavored Markdown Table extension block-start scan](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8) (PKSA-m4t9-vsgq-8khn) | high | blocker | No; approval required |
| 4/5 | `composer.lock:2214` | league/flysystem 3.35.2 | [Flysystem: WhitespacePathNormalizer's control-character (CorruptedPathDetected) check is bypassed by malformed UTF-8 in the path, affecting every adapter](https://github.com/advisories/GHSA-cxf4-7mrp-vvpr) (PKSA-w9tt-7782-78jx) | low | important | No; approval required |
| 4/5 | `composer.lock:4783` | symfony/filesystem v8.1.6 | [Filesystem Path Treats Any Prefix Before "://" as a Root, Letting Paths Escape Their Base Directory](https://github.com/symfony/symfony/security/advisories/GHSA-6prc-h956-hxjq) (PKSA-g499-cm4q-v35y) | not supplied | important | No; approval required |
| 4/5 | `composer.lock:4922` | symfony/http-foundation v8.1.4 | [The "private_ranges" Trusted Proxies Shortcut Trusts Clients Presenting an IPv4-Mapped, 6to4, Teredo or NAT64 Address as Reverse Proxies](https://github.com/symfony/symfony/security/advisories/GHSA-xg2q-4rrq-657q) (PKSA-nxjs-s6hn-vb6m) | not supplied | important | No; approval required |
| 4/5 | `composer.lock:4922` | symfony/http-foundation v8.1.4 | [CVE-2026-118514: HEAD Requests Bypass the methods Option of security.access_control Rules and Firewalls: Incomplete Fix for CVE-2026-45075](https://symfony.com/cve-2026-118514) (PKSA-24ph-xkrh-y79j) | not supplied | important | No; approval required |
| 4/5 | `composer.lock:4922` | symfony/http-foundation v8.1.4 | [Path Rules Enforced in Front of PHP Can Be Bypassed by Appending the Path to the Script Name](https://github.com/symfony/symfony/security/advisories/GHSA-3h27-hjv7-fqjx) (PKSA-pr6q-2w5s-kzzf) | not supplied | important | No; approval required |
| 4/5 | `composer.lock:4922` | symfony/http-foundation v8.1.4 | [Generated URLs Can Point to a Host Chosen by the Client](https://github.com/symfony/symfony/security/advisories/GHSA-5mx8-mwrp-5693) (PKSA-b2gg-vk1g-6d5q) | not supplied | important | No; approval required |
| 4/5 | `composer.lock:4922` | symfony/http-foundation v8.1.4 | [CVE-2026-108082: ReDoS in HeaderUtils::split() Reachable Through the Cache-Control Request Header](https://symfony.com/cve-2026-108082) (PKSA-xmwy-rmx5-m7h4) | high | blocker | No; approval required |
| 4/5 | `composer.lock:5003` | symfony/http-kernel v8.1.4 | [Inline Fragments Let Untrusted Forwarded Headers Set the Host, Scheme and Port of the URLs Generated for the Main Request](https://github.com/symfony/symfony/security/advisories/GHSA-wg5q-m2ww-f3c9) (PKSA-3gxv-7xhq-mgmz) | not supplied | important | No; approval required |
| 4/5 | `composer.lock:5193` | symfony/mime v8.1.4 | [CVE-2026-45070: Email Header Injection via Non-Token Characters in Mime Parameter Names](https://symfony.com/cve-2026-45070) (PKSA-wtxr-p26d-nn42) | medium | important | No; approval required |
| 4/5 | `composer.lock:6253` | symfony/routing v8.1.2 | [CVE-2026-118516: Host Placeholders Can Match the Start of the Path in Compiled URL Matchers, Bypassing Path-Based Access Control](https://symfony.com/cve-2026-118516) (PKSA-y8c5-1b7f-r1gj) | not supplied | important | No; approval required |
| 4/5 | `composer.lock:11050` | symfony/yaml v8.1.2 | [Exponential and Quadratic Parse Times in the YAML Parser Let a Single Document Hold a Process](https://github.com/symfony/symfony/security/advisories/GHSA-hwfg-qwvh-2wfx) (PKSA-nktx-f1n1-b4yd) | not supplied | important | No; approval required |
| 4/5 | `composer.lock:11050` | symfony/yaml v8.1.2 | [CVE-2026-109413: Unbounded Recursion in the YAML Inline Lexer Lets a Single Document Crash the Process](https://symfony.com/cve-2026-109413) (PKSA-xz3r-9gp4-4m1z) | high | blocker | No; approval required |

## Phase 1 inventory and final inventory delta

Initial read-only inventory: 279 application routes, 149 Blade templates, 52 JavaScript files and 9 CSS files. Local configured route hosts were localhost for admin and logistics.localhost for logistics, supplied through portal configuration. Domainless buyer/seller/shared routes also run through EnforcePortalHost and portal-session isolation; absence of a route-level domain does not establish access from every host. Courier API routes intentionally use bearer authentication rather than web role sessions and logistics-host page routing.

Final delta: removed one unreachable pending-registration route and one misplaced non-Blade trait copy; added seven error templates and two JavaScript handlers. The full final route and file inventories follow. They include components, partials and layouts; Blade/PHP support files under resources/views are noted separately. The route table includes resolved auth/role/domain evidence from `route:list --json --except-vendor`.

Final counts: 278 application routes; 156 Blade templates; 54 JavaScript files; 9 CSS files.

### Routes by configured domain and role group

#### domainless (global host middleware applies) / buyer (45)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | buyer | buyer.home | App\Http\Controllers\Buyer\HomeController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/account | buyer.account.index | App\Http\Controllers\Buyer\AccountController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/account/address | buyer.account.address | App\Http\Controllers\Buyer\AccountController@updateAddress | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/account/avatar | buyer.account.avatar | App\Http\Controllers\Buyer\AccountController@updateAvatar | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | buyer/account/avatar | buyer.account.avatar.destroy | App\Http\Controllers\Buyer\AccountController@destroyAvatar | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/account/password | buyer.account.password | App\Http\Controllers\Buyer\AccountController@updatePassword | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/account/profile | buyer.account.profile | App\Http\Controllers\Buyer\AccountController@updateProfile | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/addresses | buyer.addresses.index | App\Http\Controllers\Buyer\AddressController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/addresses | buyer.addresses.store | App\Http\Controllers\Buyer\AddressController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/addresses/locations | buyer.addresses.locations | App\Http\Controllers\Buyer\AddressController@locations | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| PUT / PATCH | buyer/addresses/{address} | buyer.addresses.update | App\Http\Controllers\Buyer\AddressController@update | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | buyer/addresses/{address} | buyer.addresses.destroy | App\Http\Controllers\Buyer\AddressController@destroy | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| PATCH | buyer/addresses/{address}/default | buyer.addresses.default | App\Http\Controllers\Buyer\AddressController@setDefault | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/cart | buyer.cart.index | App\Http\Controllers\Buyer\CartController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | buyer/cart | buyer.cart.destroyMany | App\Http\Controllers\Buyer\CartController@destroyMany | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/cart/item/{cartItem} | buyer.cart.update | App\Http\Controllers\Buyer\CartController@update | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | buyer/cart/item/{cartItem} | buyer.cart.destroy | App\Http\Controllers\Buyer\CartController@destroy | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/cart/{product} | buyer.cart.store | App\Http\Controllers\Buyer\CartController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/checkout | buyer.checkout.index | App\Http\Controllers\Buyer\CheckoutController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/checkout | buyer.checkout.store | App\Http\Controllers\Buyer\CheckoutController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/orders | buyer.orders.index | App\Http\Controllers\Buyer\OrderController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/orders/{order}/buy-again | buyer.orders.buy-again | App\Http\Controllers\Buyer\OrderController@buyAgain | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/orders/{order}/cancel | buyer.orders.cancel | App\Http\Controllers\Buyer\OrderController@cancel | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:20,1 |
| POST | buyer/orders/{order}/cancel-request/withdraw | buyer.orders.cancel-request.withdraw | App\Http\Controllers\Buyer\OrderController@withdrawCancelRequest | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/orders/{order}/confirm | buyer.orders.confirm | App\Http\Controllers\Buyer\OrderController@confirmReceived | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/orders/{order}/items/{item}/review | buyer.orders.review.store | App\Http\Controllers\Buyer\ReviewController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:20,1 |
| GET / HEAD | buyer/orders/{order}/return | buyer.orders.return.create | App\Http\Controllers\Buyer\ReturnController@create | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/orders/{order}/return | buyer.orders.return.store | App\Http\Controllers\Buyer\ReturnController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET / HEAD | buyer/products | buyer.products.index | App\Http\Controllers\Buyer\ProductController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/products/{product} | buyer.products.show | App\Http\Controllers\Buyer\ProductController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/products/{product}/stock | buyer.products.stock | App\Http\Controllers\Buyer\ProductController@stock | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:30,1 |
| GET / HEAD | buyer/returns/{orderReturn} | buyer.returns.show | App\Http\Controllers\Buyer\ReturnController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/returns/{orderReturn}/escalate | buyer.returns.escalate | App\Http\Controllers\Buyer\ReturnController@escalate | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/returns/{orderReturn}/ship | buyer.returns.ship | App\Http\Controllers\Buyer\ReturnController@ship | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/returns/{orderReturn}/withdraw | buyer.returns.withdraw | App\Http\Controllers\Buyer\ReturnController@withdraw | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/sellers/{seller}/follow | buyer.sellers.follow | App\Http\Controllers\Buyer\SellerFollowController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:60,1 |
| DELETE | buyer/sellers/{seller}/follow | buyer.sellers.unfollow | App\Http\Controllers\Buyer\SellerFollowController@destroy | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:60,1 |
| GET / HEAD | buyer/shop/{seller} | buyer.shop.show | App\Http\Controllers\Buyer\ShopController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/vouchers | buyer.vouchers.index | App\Http\Controllers\Buyer\VoucherController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/vouchers/apply | buyer.vouchers.apply | App\Http\Controllers\Buyer\VoucherController@apply | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | buyer/vouchers/dropdown | buyer.vouchers.dropdown | App\Http\Controllers\Buyer\VoucherController@dropdown | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/vouchers/{shopVoucher}/claim | buyer.vouchers.claim | App\Http\Controllers\Buyer\VoucherController@claim | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:30,1 |
| GET / HEAD | buyer/wishlist | buyer.wishlist.index | App\Http\Controllers\Buyer\WishlistController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | buyer/wishlist/{product} | buyer.wishlist.store | App\Http\Controllers\Buyer\WishlistController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:60,1 |
| DELETE | buyer/wishlist/{product} | buyer.wishlist.destroy | App\Http\Controllers\Buyer\WishlistController@destroy | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsBuyer, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:60,1 |

#### domainless (global host middleware applies) / courier-api (37)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| POST | api/courier/account/avatar | (unnamed) | App\Http\Controllers\Api\Courier\AccountController@updateAvatar | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | api/courier/account/avatar | (unnamed) | App\Http\Controllers\Api\Courier\AccountController@destroyAvatar | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/account/password | (unnamed) | App\Http\Controllers\Api\Courier\AccountController@updatePassword | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| POST | api/courier/account/profile | (unnamed) | App\Http\Controllers\Api\Courier\AccountController@updateProfile | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/chat/contacts | (unnamed) | App\Http\Controllers\Api\Courier\ChatController@contacts | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/chat/conversations | (unnamed) | App\Http\Controllers\Api\Courier\ChatController@conversations | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/chat/open | (unnamed) | App\Http\Controllers\Api\Courier\ChatController@open | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/chat/unread-count | (unnamed) | App\Http\Controllers\Api\Courier\ChatController@unreadCount | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/chat/{conversation}/hide | (unnamed) | App\Http\Controllers\Api\Courier\ChatController@destroy | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/chat/{conversation}/messages | (unnamed) | App\Http\Controllers\Api\Courier\ChatController@messages | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/chat/{conversation}/messages | (unnamed) | App\Http\Controllers\Api\Courier\ChatController@store | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:60,1 |
| GET / HEAD | api/courier/dashboard | (unnamed) | App\Http\Controllers\Api\Courier\DashboardController@summary | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/deliveries | (unnamed) | App\Http\Controllers\Api\Courier\DeliveryController@index | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/deliveries/{order}/delivered | (unnamed) | App\Http\Controllers\Api\Courier\DeliveryController@delivered | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/deliveries/{order}/failed | (unnamed) | App\Http\Controllers\Api\Courier\DeliveryController@failed | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/deliveries/{order}/out | (unnamed) | App\Http\Controllers\Api\Courier\DeliveryController@out | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/earnings | (unnamed) | App\Http\Controllers\Api\Courier\EarningsController@index | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/earnings/history | (unnamed) | App\Http\Controllers\Api\Courier\EarningsController@history | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/earnings/rate | (unnamed) | App\Http\Controllers\Api\Courier\EarningsController@rate | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/earnings/summary | (unnamed) | App\Http\Controllers\Api\Courier\EarningsController@index | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/forgot-password | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@forgotPassword | api, Illuminate\Routing\Middleware\ThrottleRequests:3,1 |
| GET / HEAD | api/courier/history | (unnamed) | App\Http\Controllers\Api\Courier\HistoryController@index | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/login | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@login | api, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| POST | api/courier/logout | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@logout | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/me | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@me | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/notifications | (unnamed) | App\Http\Controllers\Api\Courier\NotificationController@index | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/notifications/read-all | (unnamed) | App\Http\Controllers\Api\Courier\NotificationController@readAll | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/notifications/{id}/read | (unnamed) | App\Http\Controllers\Api\Courier\NotificationController@read | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | api/courier/pickups | (unnamed) | App\Http\Controllers\Api\Courier\PickupController@index | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/pickups/{order}/accept | (unnamed) | App\Http\Controllers\Api\Courier\PickupController@accept | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/pickups/{order}/confirm | (unnamed) | App\Http\Controllers\Api\Courier\PickupController@confirm | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/pickups/{order}/decline | (unnamed) | App\Http\Controllers\Api\Courier\PickupController@decline | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier:approved, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/reapply | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@reapply | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\EnsureCourier, Illuminate\Routing\Middleware\ThrottleRequests:courier-reads, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | api/courier/register | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@register | api, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| POST | api/courier/resend-otp | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@resendOtp | api, Illuminate\Routing\Middleware\ThrottleRequests:3,1 |
| POST | api/courier/reset-password | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@resetPassword | api, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| POST | api/courier/verify-otp | (unnamed) | App\Http\Controllers\Api\Courier\AuthController@verifyOtp | api, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |

#### domainless (global host middleware applies) / help (2)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | help | help.index | App\Http\Controllers\SupportController@index | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | help/tickets | help.tickets.store | App\Http\Controllers\SupportController@store | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |

#### domainless (global host middleware applies) / home (1)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | / | home | App\Http\Controllers\LandingController@index | web |

#### domainless (global host middleware applies) / login (1)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | login | login | App\Http\Controllers\Auth\LoginController@create | web |

#### domainless (global host middleware applies) / logout (1)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| POST | logout | logout | App\Http\Controllers\Auth\LoginController@destroy | web |

#### domainless (global host middleware applies) / messages (18)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | messages | messages.index | App\Http\Controllers\MessagingController@index | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages | messages.store | App\Http\Controllers\MessagingController@store | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| GET / HEAD | messages/attachments/{attachment} | messages.attachment | App\Http\Controllers\MessagingController@attachment | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| GET / HEAD | messages/contacts | messages.contacts | App\Http\Controllers\MessagingController@contacts | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages/forward/{message} | messages.forward | App\Http\Controllers\MessagingController@forward | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages/open | messages.open | App\Http\Controllers\MessagingController@open | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| GET / HEAD | messages/recent-purchases | messages.recent-purchases | App\Http\Controllers\MessagingController@recentPurchases | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| GET / HEAD | messages/support | messages.support | App\Http\Controllers\MessagingController@openSupport | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| GET / HEAD | messages/unread-count | messages.unread-count | App\Http\Controllers\MessagingController@unreadCount | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| GET / HEAD | messages/widget-data | messages.widget-data | App\Http\Controllers\MessagingController@widgetData | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages/{conversation}/claim | messages.claim | App\Http\Controllers\MessagingController@claim | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages/{conversation}/delete | messages.destroy | App\Http\Controllers\MessagingController@destroy | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| GET / HEAD | messages/{conversation}/details | messages.details | App\Http\Controllers\MessagingController@details | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| GET / HEAD | messages/{conversation}/messages | messages.thread | App\Http\Controllers\MessagingController@messages | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages/{conversation}/messages | messages.thread.store | App\Http\Controllers\MessagingController@storeMessage | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages/{conversation}/reopen | messages.reopen | App\Http\Controllers\MessagingController@reopen | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages/{conversation}/resolve | messages.resolve | App\Http\Controllers\MessagingController@resolve | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |
| POST | messages/{conversation}/unclaim | messages.unclaim | App\Http\Controllers\MessagingController@unclaim | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:messaging |

#### domainless (global host middleware applies) / notifications (4)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | notifications | notifications.index | App\Http\Controllers\NotificationController@index | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | notifications/feed | notifications.feed | App\Http\Controllers\NotificationController@feed | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:120,1 |
| POST | notifications/read-all | notifications.read-all | App\Http\Controllers\NotificationController@readAll | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | notifications/{id}/read | notifications.read | App\Http\Controllers\NotificationController@read | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations |

#### domainless (global host middleware applies) / password (7)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | forgot-password | password.request | App\Http\Controllers\Auth\PasswordResetController@requestForm | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| POST | forgot-password | password.email | App\Http\Controllers\Auth\PasswordResetController@send | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated, Illuminate\Routing\Middleware\ThrottleRequests:20,60 |
| POST | forgot-password/resend | password.resend | App\Http\Controllers\Auth\PasswordResetController@resend | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated, Illuminate\Routing\Middleware\ThrottleRequests:20,60 |
| GET / HEAD | forgot-password/reset | password.reset | App\Http\Controllers\Auth\PasswordResetController@resetForm | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| POST | forgot-password/reset | password.update | App\Http\Controllers\Auth\PasswordResetController@reset | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET / HEAD | forgot-password/verify | password.verify | App\Http\Controllers\Auth\PasswordResetController@verifyForm | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| POST | forgot-password/verify | password.verify.store | App\Http\Controllers\Auth\PasswordResetController@verify | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated, Illuminate\Routing\Middleware\ThrottleRequests:20,1 |

#### domainless (global host middleware applies) / policies (1)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | policies/{policy} | policies.show | App\Http\Controllers\PolicyController@show | web |

#### domainless (global host middleware applies) / privacy (2)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | privacy | privacy | App\Http\Controllers\PolicyController@privacy | web |
| GET / HEAD | privacy/content | privacy.content | App\Http\Controllers\PolicyController@privacyContent | web |

#### domainless (global host middleware applies) / public-api (5)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | api/hubs | (unnamed) | App\Http\Controllers\Api\HubController@index | api, Illuminate\Routing\Middleware\ThrottleRequests:60,1 |
| GET / HEAD | api/hubs/{hub}/barangays | (unnamed) | App\Http\Controllers\Api\HubController@barangays | api, Illuminate\Routing\Middleware\ThrottleRequests:60,1 |
| POST | login | (unnamed) | App\Http\Controllers\Auth\LoginController@store | web, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| POST | register/buyer | (unnamed) | App\Http\Controllers\Auth\BuyerRegistrationController@store | web, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| POST | register/seller | (unnamed) | App\Http\Controllers\Auth\SellerRegistrationController@store | web, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |

#### domainless (global host middleware applies) / register (6)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | register | register | Closure | web |
| GET / HEAD | register/buyer | register.buyer | App\Http\Controllers\Auth\BuyerRegistrationController@create | web |
| GET / HEAD | register/seller | register.seller | App\Http\Controllers\Auth\SellerRegistrationController@create | web |
| POST | register/{role}/resend-otp | register.resend-otp | App\Http\Controllers\Auth\LoginController@resendOtp | web, Illuminate\Routing\Middleware\ThrottleRequests:3,1 |
| GET / HEAD | register/{role}/verify | register.otp.form | App\Http\Controllers\Auth\LoginController@showOtpForm | web |
| POST | register/{role}/verify | register.otp.verify | App\Http\Controllers\Auth\LoginController@verifyOtp | web, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |

#### domainless (global host middleware applies) / returns (1)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | returns/media/{returnMedia} | returns.media | App\Http\Controllers\ReturnMediaController@show | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:mutations |

#### domainless (global host middleware applies) / seller (51)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | seller/account | seller.account.index | App\Http\Controllers\Seller\AccountController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/account/address | seller.account.address | App\Http\Controllers\Seller\AccountController@updateAddress | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/account/avatar | seller.account.avatar | App\Http\Controllers\Seller\AccountController@updateAvatar | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | seller/account/avatar | seller.account.avatar.destroy | App\Http\Controllers\Seller\AccountController@destroyAvatar | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/account/password | seller.account.password | App\Http\Controllers\Seller\AccountController@updatePassword | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/account/profile | seller.account.profile | App\Http\Controllers\Seller\AccountController@updateProfile | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/dashboard | seller.dashboard | App\Http\Controllers\Seller\DashboardController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/orders | seller.orders.index | App\Http\Controllers\Seller\OrderController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/orders/{order} | seller.orders.show | App\Http\Controllers\Seller\OrderController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/orders/{order}/cancel | seller.orders.cancel | App\Http\Controllers\Seller\OrderController@cancel | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:20,1 |
| POST | seller/orders/{order}/cancel-pickup | seller.orders.cancel-pickup | App\Http\Controllers\Seller\OrderController@cancelPickup | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/orders/{order}/cancel-request/approve | seller.orders.cancel-request.approve | App\Http\Controllers\Seller\OrderController@approveCancel | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/orders/{order}/cancel-request/decline | seller.orders.cancel-request.decline | App\Http\Controllers\Seller\OrderController@declineCancel | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/orders/{order}/request-pickup | seller.orders.request-pickup | App\Http\Controllers\Seller\OrderController@requestPickup | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/orders/{order}/status | seller.orders.status | App\Http\Controllers\Seller\OrderController@updateStatus | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/orders/{order}/waybill | seller.orders.waybill | App\Http\Controllers\Seller\WaybillController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/orders/{order}/waybill/view | seller.orders.waybill.view | App\Http\Controllers\Seller\WaybillController@preview | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/products | seller.products.index | App\Http\Controllers\Seller\ProductController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/products | seller.products.store | App\Http\Controllers\Seller\ProductController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/products/bulk | seller.products.bulk | App\Http\Controllers\Seller\ProductController@bulk | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/products/create | seller.products.create | App\Http\Controllers\Seller\ProductController@create | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/products/discounts | seller.products.discounts.index | App\Http\Controllers\Seller\ProductDiscountController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/products/discounts | seller.products.discounts.store | App\Http\Controllers\Seller\ProductDiscountController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| PUT | seller/products/discounts/{discount} | seller.products.discounts.update | App\Http\Controllers\Seller\ProductDiscountController@update | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | seller/products/discounts/{discount} | seller.products.discounts.destroy | App\Http\Controllers\Seller\ProductDiscountController@destroy | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| PUT | seller/products/{product} | seller.products.update | App\Http\Controllers\Seller\ProductController@update | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | seller/products/{product} | seller.products.destroy | App\Http\Controllers\Seller\ProductController@destroy | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/products/{product}/duplicate | seller.products.duplicate | App\Http\Controllers\Seller\ProductController@duplicate | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/products/{product}/edit | seller.products.edit | App\Http\Controllers\Seller\ProductController@edit | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| PATCH | seller/products/{product}/quick | seller.products.quick | App\Http\Controllers\Seller\ProductController@quickUpdate | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| PATCH | seller/products/{product}/status | seller.products.status | App\Http\Controllers\Seller\ProductController@updateStatus | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/reports | seller.reports.index | App\Http\Controllers\Seller\ReportController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/reports/export/csv | seller.reports.export.csv | App\Http\Controllers\Seller\ReportController@exportCsv | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/reports/export/pdf | seller.reports.export.pdf | App\Http\Controllers\Seller\ReportController@exportPdf | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/returns | seller.returns.index | App\Http\Controllers\Seller\ReturnController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/returns/{orderReturn} | seller.returns.show | App\Http\Controllers\Seller\ReturnController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/returns/{orderReturn}/approve | seller.returns.approve | App\Http\Controllers\Seller\ReturnController@approve | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/returns/{orderReturn}/decline | seller.returns.decline | App\Http\Controllers\Seller\ReturnController@decline | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/returns/{orderReturn}/problem | seller.returns.problem | App\Http\Controllers\Seller\ReturnController@problem | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/returns/{orderReturn}/received | seller.returns.received | App\Http\Controllers\Seller\ReturnController@received | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/reviews | seller.reviews.index | App\Http\Controllers\Seller\ReviewController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/reviews/{review}/reply | seller.reviews.reply | App\Http\Controllers\Seller\ReviewController@reply | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:30,1 |
| DELETE | seller/reviews/{review}/reply | seller.reviews.reply.destroy | App\Http\Controllers\Seller\ReviewController@destroyReply | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/vouchers | seller.vouchers.index | App\Http\Controllers\Seller\ShopVoucherController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/vouchers | seller.vouchers.store | App\Http\Controllers\Seller\ShopVoucherController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/vouchers/create | seller.vouchers.create | App\Http\Controllers\Seller\ShopVoucherController@create | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/vouchers/generate-code | seller.vouchers.generate-code | App\Http\Controllers\Seller\ShopVoucherController@generateCode | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/vouchers/{shopVoucher} | seller.vouchers.show | App\Http\Controllers\Seller\ShopVoucherController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| PUT / PATCH | seller/vouchers/{shopVoucher} | seller.vouchers.update | App\Http\Controllers\Seller\ShopVoucherController@update | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | seller/vouchers/{shopVoucher}/edit | seller.vouchers.edit | App\Http\Controllers\Seller\ShopVoucherController@edit | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | seller/vouchers/{shopVoucher}/toggle | seller.vouchers.toggle | App\Http\Controllers\Seller\ShopVoucherController@toggle | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsSeller, Illuminate\Routing\Middleware\ThrottleRequests:mutations |

#### domainless (global host middleware applies) / terms (2)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | terms | terms | App\Http\Controllers\PolicyController@terms | web |
| GET / HEAD | terms/content | terms.content | App\Http\Controllers\PolicyController@termsContent | web |

#### localhost / admin (37)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | admin/dashboard | admin.dashboard | App\Http\Controllers\Admin\DashboardController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/hubs | admin.hubs.companies | App\Http\Controllers\Admin\HubAdminController@companies | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/hubs/barangays | admin.hubs.barangays | App\Http\Controllers\Admin\HubAdminController@barangays | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/hubs/transfers | admin.hubs.transfers | App\Http\Controllers\Admin\HubAdminController@transfers | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/logs | admin.logs.index | App\Http\Controllers\Admin\ActivityLogController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/registrations/buyers | admin.registrations.buyers.index | App\Http\Controllers\Admin\BuyerManagementController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/buyers/{buyerProfile}/account-status | admin.registrations.buyers.accountStatus | App\Http\Controllers\Admin\BuyerManagementController@updateAccountStatus | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/buyers/{buyerProfile}/approve | admin.registrations.buyers.approve | App\Http\Controllers\Admin\BuyerManagementController@approve | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/registrations/buyers/{buyerProfile}/details | admin.registrations.buyers.details | App\Http\Controllers\Admin\BuyerManagementController@details | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/buyers/{buyerProfile}/reject | admin.registrations.buyers.reject | App\Http\Controllers\Admin\BuyerManagementController@reject | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/registrations/logistics | admin.registrations.logistics.index | App\Http\Controllers\Admin\LogisticsManagementController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/logistics/{logisticsProfile}/account-status | admin.registrations.logistics.accountStatus | App\Http\Controllers\Admin\LogisticsManagementController@updateAccountStatus | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/logistics/{logisticsProfile}/approve | admin.registrations.logistics.approve | App\Http\Controllers\Admin\LogisticsManagementController@approve | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/registrations/logistics/{logisticsProfile}/details | admin.registrations.logistics.details | App\Http\Controllers\Admin\LogisticsManagementController@details | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/logistics/{logisticsProfile}/reject | admin.registrations.logistics.reject | App\Http\Controllers\Admin\LogisticsManagementController@reject | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/registrations/sellers | admin.registrations.sellers.index | App\Http\Controllers\Admin\SellerManagementController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/sellers/{sellerProfile}/account-status | admin.registrations.sellers.accountStatus | App\Http\Controllers\Admin\SellerManagementController@updateAccountStatus | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/sellers/{sellerProfile}/approve | admin.registrations.sellers.approve | App\Http\Controllers\Admin\SellerManagementController@approve | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/registrations/sellers/{sellerProfile}/details | admin.registrations.sellers.details | App\Http\Controllers\Admin\SellerManagementController@details | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/registrations/sellers/{sellerProfile}/reject | admin.registrations.sellers.reject | App\Http\Controllers\Admin\SellerManagementController@reject | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/returns | admin.returns.index | App\Http\Controllers\Admin\ReturnController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/returns/{orderReturn} | admin.returns.show | App\Http\Controllers\Admin\ReturnController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/returns/{orderReturn}/decide | admin.returns.decide | App\Http\Controllers\Admin\ReturnController@decide | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/returns/{orderReturn}/refunded | admin.returns.refunded | App\Http\Controllers\Admin\ReturnController@markRefunded | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/settings | admin.settings.index | App\Http\Controllers\Admin\PlatformSettingController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/settings/announcements | admin.settings.announcements.index | App\Http\Controllers\Admin\PlatformSettingController@announcements | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/settings/announcements | admin.settings.announcements.store | App\Http\Controllers\Admin\PlatformSettingController@storeAnnouncement | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/settings/announcements/{announcement} | admin.settings.announcements.update | App\Http\Controllers\Admin\PlatformSettingController@updateAnnouncement | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | admin/settings/announcements/{announcement} | admin.settings.announcements.destroy | App\Http\Controllers\Admin\PlatformSettingController@destroyAnnouncement | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/settings/announcements/{announcement}/toggle | admin.settings.announcements.toggle | App\Http\Controllers\Admin\PlatformSettingController@toggleAnnouncement | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/settings/general | admin.settings.general | App\Http\Controllers\Admin\PlatformSettingController@updateGeneral | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/settings/policies | admin.settings.policies.index | App\Http\Controllers\Admin\PlatformSettingController@policies | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/settings/policies/{policy} | admin.settings.policies.update | App\Http\Controllers\Admin\PlatformSettingController@updatePolicy | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/settings/shipping | admin.settings.shipping.index | App\Http\Controllers\Admin\PlatformSettingController@shipping | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | admin/settings/shipping | admin.settings.shipping | App\Http\Controllers\Admin\PlatformSettingController@updateShipping | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/transactions | admin.transactions.index | App\Http\Controllers\Admin\TransactionController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | admin/transactions/export/csv | admin.transactions.export.csv | App\Http\Controllers\Admin\TransactionController@exportCsv | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsAdmin, Illuminate\Routing\Middleware\ThrottleRequests:mutations |

#### logistics.localhost / logistics (55)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | logistics/application | logistics.application | App\Http\Controllers\Logistics\PortalController@application | web, Illuminate\Auth\Middleware\Authenticate |
| POST | logistics/application | logistics.application.resubmit | App\Http\Controllers\Logistics\PortalController@resubmit | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:5,1 |
| GET / HEAD | logistics/areas | logistics.areas.index | App\Http\Controllers\Logistics\ServiceAreaController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/areas | logistics.areas.store | App\Http\Controllers\Logistics\ServiceAreaController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | logistics/areas/{area} | logistics.areas.destroy | App\Http\Controllers\Logistics\ServiceAreaController@destroy | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/dashboard | logistics.dashboard | App\Http\Controllers\Logistics\DashboardController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/deliveries | logistics.deliveries.index | App\Http\Controllers\Logistics\DeliveryController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/deliveries/{order}/delivered | logistics.deliveries.delivered | App\Http\Controllers\Logistics\DeliveryController@delivered | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/deliveries/{order}/failed | logistics.deliveries.failed | App\Http\Controllers\Logistics\DeliveryController@failed | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/deliveries/{order}/out | logistics.deliveries.out | App\Http\Controllers\Logistics\DeliveryController@outForDelivery | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/deliveries/{order}/reschedule | logistics.deliveries.reschedule | App\Http\Controllers\Logistics\DeliveryController@reschedule | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/deliveries/{order}/return | logistics.deliveries.return | App\Http\Controllers\Logistics\DeliveryController@returnToSeller | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/inbound | logistics.inbound.index | App\Http\Controllers\Logistics\InboundController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/inbound/bags/{bag} | logistics.inbound.show | App\Http\Controllers\Logistics\InboundController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/inbound/bags/{bag}/close | logistics.inbound.close | App\Http\Controllers\Logistics\InboundController@close | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/inbound/bags/{bag}/parcels/{order} | logistics.inbound.parcels.confirm | App\Http\Controllers\Logistics\InboundController@confirmParcel | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/inbound/bags/{bag}/scan | logistics.inbound.scan | App\Http\Controllers\Logistics\InboundController@scan | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:120,1 |
| POST | logistics/inbound/bags/{bag}/verify | logistics.inbound.verify | App\Http\Controllers\Logistics\InboundController@verify | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:120,1 |
| POST | logistics/inbound/scan | logistics.inbound.scan.index | App\Http\Controllers\Logistics\InboundController@scanIndex | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:120,1 |
| GET / HEAD | logistics/incoming | logistics.incoming.index | App\Http\Controllers\Logistics\IncomingParcelController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/incoming/{order}/confirm-arrival | logistics.incoming.confirm-arrival | App\Http\Controllers\Logistics\IncomingParcelController@confirmArrival | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/outbound | logistics.outbound.index | App\Http\Controllers\Logistics\OutboundController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/outbound/bags | logistics.outbound.bags.store | App\Http\Controllers\Logistics\OutboundController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/outbound/bags/{bag} | logistics.outbound.show | App\Http\Controllers\Logistics\OutboundController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/outbound/bags/{bag}/dispatch | logistics.outbound.dispatch | App\Http\Controllers\Logistics\OutboundController@dispatch | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/outbound/bags/{bag}/manifest | logistics.outbound.manifest | App\Http\Controllers\Logistics\OutboundController@manifest | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/outbound/bags/{bag}/manifest/printed | logistics.outbound.manifest.printed | App\Http\Controllers\Logistics\OutboundController@markManifestPrinted | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/outbound/bags/{bag}/orders | logistics.outbound.orders.store | App\Http\Controllers\Logistics\OutboundController@addOrders | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| DELETE | logistics/outbound/bags/{bag}/orders/{order} | logistics.outbound.orders.destroy | App\Http\Controllers\Logistics\OutboundController@removeOrder | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/outbound/bags/{bag}/seal | logistics.outbound.seal | App\Http\Controllers\Logistics\OutboundController@seal | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/pickups | logistics.pickups.index | App\Http\Controllers\Logistics\PickupController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/pickups/{order}/assign | logistics.pickups.assign | App\Http\Controllers\Logistics\PickupController@assign | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/pickups/{order}/confirm | logistics.pickups.confirm | App\Http\Controllers\Logistics\PickupController@confirmPickup | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/pickups/{order}/decline | logistics.pickups.decline | App\Http\Controllers\Logistics\PickupController@decline | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/reports | logistics.reports.index | App\Http\Controllers\Logistics\ReportController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/reports/export/csv | logistics.reports.export.csv | App\Http\Controllers\Logistics\ReportController@exportCsv | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/reports/export/pdf | logistics.reports.export.pdf | App\Http\Controllers\Logistics\ReportController@exportPdf | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/rider-rate | logistics.rider-rate.index | App\Http\Controllers\Logistics\RiderRateController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/rider-rate | logistics.rider-rate.store | App\Http\Controllers\Logistics\RiderRateController@store | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/riders | logistics.riders.index | App\Http\Controllers\Logistics\RiderController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/riders/{rider} | logistics.riders.show | App\Http\Controllers\Logistics\RiderController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/riders/{rider}/approve | logistics.riders.approve | App\Http\Controllers\Logistics\RiderController@approve | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/riders/{rider}/documents/{document} | logistics.riders.document | App\Http\Controllers\Logistics\RiderController@document | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/riders/{rider}/reject | logistics.riders.reject | App\Http\Controllers\Logistics\RiderController@reject | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/riders/{rider}/toggle-active | logistics.riders.toggle-active | App\Http\Controllers\Logistics\RiderController@toggleActive | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/sorting | logistics.sorting.index | App\Http\Controllers\Logistics\SortingController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/sorting/assign-area | logistics.sorting.assign-area | App\Http\Controllers\Logistics\SortingController@assignArea | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| GET / HEAD | logistics/sorting/sessions | logistics.sorting.sessions.index | App\Http\Controllers\Logistics\SortingSessionController@index | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/sorting/sessions/start | logistics.sorting.sessions.start | App\Http\Controllers\Logistics\SortingSessionController@start | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/sorting/sessions/{session}/end | logistics.sorting.sessions.end | App\Http\Controllers\Logistics\SortingSessionController@end | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/sorting/sessions/{session}/scan | logistics.sorting.sessions.scan | App\Http\Controllers\Logistics\SortingSessionController@scan | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations, Illuminate\Routing\Middleware\ThrottleRequests:120,1 |
| GET / HEAD | logistics/sorting/sessions/{session}/summary | logistics.sorting.sessions.summary | App\Http\Controllers\Logistics\SortingSessionController@summary | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/sorting/sort-all | logistics.sorting.sort-all | App\Http\Controllers\Logistics\SortingController@sortAll | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/sorting/{order}/assign | logistics.sorting.assign | App\Http\Controllers\Logistics\SortingController@assign | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |
| POST | logistics/sorting/{order}/sort | logistics.sorting.sort | App\Http\Controllers\Logistics\SortingController@sort | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserIsLogistics, App\Http\Middleware\ResolveHubScope, Illuminate\Routing\Middleware\ThrottleRequests:mutations |

#### logistics.localhost / public-api (1)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| POST | register | (unnamed) | App\Http\Controllers\Auth\LogisticsRegistrationController@store | web, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |

#### logistics.localhost / register (1)

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | register | register.logistics | App\Http\Controllers\Auth\LogisticsRegistrationController@create | web |

### Blade views, components, partials and layouts

```text
resources/views/admin/dashboard.blade.php
resources/views/admin/hubs/barangays.blade.php
resources/views/admin/hubs/companies.blade.php
resources/views/admin/hubs/transfers.blade.php
resources/views/admin/logs/index.blade.php
resources/views/admin/messages/index.blade.php
resources/views/admin/registrations/buyers/index.blade.php
resources/views/admin/registrations/logistics/index.blade.php
resources/views/admin/registrations/sellers/index.blade.php
resources/views/admin/returns/index.blade.php
resources/views/admin/returns/show.blade.php
resources/views/admin/settings/announcements.blade.php
resources/views/admin/settings/index.blade.php
resources/views/admin/settings/layout.blade.php
resources/views/admin/settings/policies.blade.php
resources/views/admin/settings/shipping.blade.php
resources/views/admin/transactions/index.blade.php
resources/views/auth/choose-role.blade.php
resources/views/auth/login.blade.php
resources/views/auth/password-recovery.blade.php
resources/views/auth/register/buyer.blade.php
resources/views/auth/register/logistics.blade.php
resources/views/auth/register/seller.blade.php
resources/views/auth/verify-otp.blade.php
resources/views/Buyer/account/index.blade.php
resources/views/Buyer/cart/index.blade.php
resources/views/Buyer/checkout/index.blade.php
resources/views/Buyer/help/index.blade.php
resources/views/Buyer/home.blade.php
resources/views/Buyer/messages/_content.blade.php
resources/views/Buyer/messages/index.blade.php
resources/views/Buyer/orders/_content.blade.php
resources/views/Buyer/orders/index.blade.php
resources/views/Buyer/products/index.blade.php
resources/views/Buyer/products/show.blade.php
resources/views/Buyer/returns/create.blade.php
resources/views/Buyer/returns/show.blade.php
resources/views/Buyer/shop/show.blade.php
resources/views/Buyer/vouchers/index.blade.php
resources/views/Buyer/wishlist/_content.blade.php
resources/views/Buyer/wishlist/index.blade.php
resources/views/components/address-modal.blade.php
resources/views/components/announcement-banner.blade.php
resources/views/components/avatar.blade.php
resources/views/components/Buyer/follow-button.blade.php
resources/views/components/Buyer/header.blade.php
resources/views/components/Buyer/message-widget.blade.php
resources/views/components/Buyer/product-card.blade.php
resources/views/components/Buyer/seller-follow-data.blade.php
resources/views/components/Buyer/wishlist-button.blade.php
resources/views/components/buyer-addresses.blade.php
resources/views/components/footer.blade.php
resources/views/components/landing/category-tile.blade.php
resources/views/components/landing/hero-art.blade.php
resources/views/components/landing/product-grid.blade.php
resources/views/components/logistics/application-fields.blade.php
resources/views/components/logistics/layout.blade.php
resources/views/components/logistics/public-header.blade.php
resources/views/components/logistics/public-layout.blade.php
resources/views/components/logo.blade.php
resources/views/components/logout-modal.blade.php
resources/views/components/nav/brand.blade.php
resources/views/components/nav/dropdown.blade.php
resources/views/components/nav/guest.blade.php
resources/views/components/nav/icon.blade.php
resources/views/components/nav/link.blade.php
resources/views/components/nav/mobile-menu.blade.php
resources/views/components/nav/notifications.blade.php
resources/views/components/nav/search.blade.php
resources/views/components/nav/shell.blade.php
resources/views/components/nav/toggle.blade.php
resources/views/components/nav/user-menu.blade.php
resources/views/components/nav/workspace.blade.php
resources/views/components/navbars/admin.blade.php
resources/views/components/navbars/logistics.blade.php
resources/views/components/navbars/seller.blade.php
resources/views/components/notification-bell-script.blade.php
resources/views/components/notification-bell.blade.php
resources/views/components/product-status.blade.php
resources/views/components/registration-header.blade.php
resources/views/components/registration-intro.blade.php
resources/views/components/seller/data-table.blade.php
resources/views/components/seller/empty-state.blade.php
resources/views/components/seller/filter-bar.blade.php
resources/views/components/seller/form-section.blade.php
resources/views/components/seller/page-header.blade.php
resources/views/components/seller/product-form.blade.php
resources/views/components/seller/product-layout.blade.php
resources/views/components/seller/stat-card.blade.php
resources/views/components/status-label.blade.php
resources/views/components/terms-consent.blade.php
resources/views/components/terms-modal.blade.php
resources/views/components/voucher-card.blade.php
resources/views/emails/logistics-decision.blade.php
resources/views/emails/otp.blade.php
resources/views/emails/password-changed.blade.php
resources/views/emails/registration-decision.blade.php
resources/views/emails/rider-decision.blade.php
resources/views/errors/403.blade.php
resources/views/errors/404.blade.php
resources/views/errors/419.blade.php
resources/views/errors/429.blade.php
resources/views/errors/500.blade.php
resources/views/errors/503.blade.php
resources/views/errors/layout.blade.php
resources/views/help/_center.blade.php
resources/views/landing/index.blade.php
resources/views/layouts/app.blade.php
resources/views/logistics/application.blade.php
resources/views/logistics/areas/index.blade.php
resources/views/logistics/dashboard.blade.php
resources/views/logistics/deliveries/index.blade.php
resources/views/logistics/help/index.blade.php
resources/views/logistics/inbound/index.blade.php
resources/views/logistics/inbound/show.blade.php
resources/views/logistics/incoming/index.blade.php
resources/views/logistics/landing.blade.php
resources/views/logistics/login.blade.php
resources/views/logistics/messages/index.blade.php
resources/views/logistics/outbound/index.blade.php
resources/views/logistics/outbound/manifest.blade.php
resources/views/logistics/outbound/show.blade.php
resources/views/logistics/pickups/_assign_form.blade.php
resources/views/logistics/pickups/index.blade.php
resources/views/logistics/reports/index.blade.php
resources/views/logistics/reports/pdf.blade.php
resources/views/logistics/rider-rate.blade.php
resources/views/logistics/riders/index.blade.php
resources/views/logistics/riders/show.blade.php
resources/views/logistics/sorting/index.blade.php
resources/views/logistics/sorting/sessions/index.blade.php
resources/views/logistics/verify-otp.blade.php
resources/views/messaging/_panel.blade.php
resources/views/notifications/_list.blade.php
resources/views/notifications/index.blade.php
resources/views/policies/show.blade.php
resources/views/seller/account/index.blade.php
resources/views/seller/dashboard.blade.php
resources/views/seller/help/index.blade.php
resources/views/seller/messages/index.blade.php
resources/views/seller/orders/index.blade.php
resources/views/seller/orders/show.blade.php
resources/views/seller/orders/waybill-preview.blade.php
resources/views/seller/orders/waybill.blade.php
resources/views/seller/products/create.blade.php
resources/views/seller/products/discounts.blade.php
resources/views/seller/products/index.blade.php
resources/views/seller/reports/index.blade.php
resources/views/seller/reports/pdf.blade.php
resources/views/seller/returns/index.blade.php
resources/views/seller/returns/show.blade.php
resources/views/seller/reviews/index.blade.php
resources/views/seller/vouchers/create.blade.php
resources/views/seller/vouchers/edit.blade.php
resources/views/seller/vouchers/index.blade.php
resources/views/seller/vouchers/show.blade.php
```

### JavaScript

```text
resources/js/app.js
resources/js/components/announcement-banner.js
resources/js/components/buyer-header.js
resources/js/pages/admin/admin-dashboard.js
resources/js/pages/admin/buyers.js
resources/js/pages/admin/logistics.js
resources/js/pages/admin/registration-review.js
resources/js/pages/admin/sellers.js
resources/js/pages/admin/transactions.js
resources/js/pages/auth/buyer-register.js
resources/js/pages/auth/login.js
resources/js/pages/auth/logistics-register.js
resources/js/pages/auth/password-recovery.js
resources/js/pages/auth/seller-register.js
resources/js/pages/buyer/account-tabs.js
resources/js/pages/buyer/account.js
resources/js/pages/buyer/buyer-cart.js
resources/js/pages/buyer/checkout.js
resources/js/pages/buyer/home.js
resources/js/pages/buyer/message-widget.js
resources/js/pages/buyer/orders.js
resources/js/pages/buyer/product-show.js
resources/js/pages/buyer/vouchers.js
resources/js/pages/help.js
resources/js/pages/landing/landing.js
resources/js/pages/logistics/dashboard.js
resources/js/pages/logistics/inbound.js
resources/js/pages/logistics/outbound-manifest.js
resources/js/pages/logistics/outbound-show.js
resources/js/pages/logistics/reports.js
resources/js/pages/logistics/scanner.js
resources/js/pages/logistics/service-areas.js
resources/js/pages/logistics/sorting.js
resources/js/pages/logistics/ui.js
resources/js/pages/messaging.js
resources/js/pages/seller/account.js
resources/js/pages/seller/dashboard.js
resources/js/pages/seller/discounts.js
resources/js/pages/seller/order-show.js
resources/js/pages/seller/product-form.js
resources/js/pages/seller/product-workspace.js
resources/js/pages/seller/products.js
resources/js/pages/seller/reports.js
resources/js/pages/seller/voucher-create.js
resources/js/pages/seller/waybill.js
resources/js/shared/buyer-addresses.js
resources/js/shared/buyerContacts.js
resources/js/shared/buyerSearch.js
resources/js/shared/landing-viewport.js
resources/js/shared/navigation.js
resources/js/shared/notifications.js
resources/js/shared/polling-request.js
resources/js/shared/psgcCascade.js
resources/js/shared/terms-modal.js
```

### CSS

```text
resources/css/app.css
resources/css/landing.css
resources/css/navbar.css
resources/css/pages/auth-register.css
resources/css/pages/logistics/dashboard.css
resources/css/pages/logistics/outbound-manifest.css
resources/css/pages/logistics/portal.css
resources/css/pages/seller/order-show.css
resources/css/pages/seller/products.css
```

### Views without direct references

After removing the copied PHP trait under resources/views, no non-Blade support files remain there. No route/controller literal view target was confirmed missing after the stale pending route removal. The following Blade candidates have no literal references in the inspected application source; they were retained because dynamic component/view resolution or outside callers have not been excluded:

- `layouts.app`
- `components.nav.notifications`

### Routes without literal named-route references

This is a candidate inventory, not a list of unreachable routes. Most entries are endpoints reached via JavaScript paths or generated prefixes. Courier endpoints are unnamed and intentionally used by a separate mobile application. No route was deleted on this evidence alone.

| Method | Name | URI |
| --- | --- | --- |
| POST | admin.registrations.buyers.accountStatus | admin/registrations/buyers/{buyerProfile}/account-status |
| POST | admin.registrations.buyers.approve | admin/registrations/buyers/{buyerProfile}/approve |
| GET / HEAD | admin.registrations.buyers.details | admin/registrations/buyers/{buyerProfile}/details |
| POST | admin.registrations.buyers.reject | admin/registrations/buyers/{buyerProfile}/reject |
| POST | admin.registrations.logistics.accountStatus | admin/registrations/logistics/{logisticsProfile}/account-status |
| POST | admin.registrations.logistics.approve | admin/registrations/logistics/{logisticsProfile}/approve |
| GET / HEAD | admin.registrations.logistics.details | admin/registrations/logistics/{logisticsProfile}/details |
| POST | admin.registrations.logistics.reject | admin/registrations/logistics/{logisticsProfile}/reject |
| POST | admin.registrations.sellers.accountStatus | admin/registrations/sellers/{sellerProfile}/account-status |
| POST | admin.registrations.sellers.approve | admin/registrations/sellers/{sellerProfile}/approve |
| GET / HEAD | admin.registrations.sellers.details | admin/registrations/sellers/{sellerProfile}/details |
| POST | admin.registrations.sellers.reject | admin/registrations/sellers/{sellerProfile}/reject |
| POST | admin.settings.announcements.update | admin/settings/announcements/{announcement} |
| POST | buyer.account.address | buyer/account/address |
| POST | buyer.addresses.store | buyer/addresses |
| PUT / PATCH | buyer.addresses.update | buyer/addresses/{address} |
| DELETE | buyer.addresses.destroy | buyer/addresses/{address} |
| PATCH | buyer.addresses.default | buyer/addresses/{address}/default |
| POST | buyer.cart.store | buyer/cart/{product} |
| POST | buyer.vouchers.apply | buyer/vouchers/apply |
| GET / HEAD | buyer.vouchers.dropdown | buyer/vouchers/dropdown |
| DELETE | buyer.wishlist.destroy | buyer/wishlist/{product} |
| POST | logistics.inbound.scan.index | logistics/inbound/scan |
| POST | logistics.sorting.sessions.start | logistics/sorting/sessions/start |
| POST | logistics.sorting.sessions.end | logistics/sorting/sessions/{session}/end |
| POST | logistics.sorting.sessions.scan | logistics/sorting/sessions/{session}/scan |
| POST | messages.store | messages |
| GET / HEAD | messages.contacts | messages/contacts |
| POST | messages.forward | messages/forward/{message} |
| GET / HEAD | messages.recent-purchases | messages/recent-purchases |
| GET / HEAD | messages.widget-data | messages/widget-data |
| POST | messages.claim | messages/{conversation}/claim |
| POST | messages.destroy | messages/{conversation}/delete |
| GET / HEAD | messages.details | messages/{conversation}/details |
| GET / HEAD | messages.thread | messages/{conversation}/messages |
| POST | messages.thread.store | messages/{conversation}/messages |
| POST | messages.reopen | messages/{conversation}/reopen |
| POST | messages.resolve | messages/{conversation}/resolve |
| POST | messages.unclaim | messages/{conversation}/unclaim |
| POST | seller.orders.status | seller/orders/{order}/status |

Confirmed usage examples: admin registration-review.js composes /{id}/details and /{id}/{action}; buyer-addresses.js constructs REST operations from a base URL; messaging.js composes conversation endpoints; scanner.js and sorting.js construct session/inbound requests. The unreferenced pending-registration GET was the only route removed. Its former route/controller target was auth.registration-pending and no caller was found.

### Confirmed unused files removed

- `app/Models/OrderCancellation.php`: wrong-path duplicate of the canonical service; no explicit include or model-class reference.
- `resources/views/auth/Concerns/HandlesOtpRegistration.php`: misplaced copy of the autoloaded controller trait; controllers use the canonical `app/Http/Controllers/Auth/Concerns/HandlesOtpRegistration.php`.
