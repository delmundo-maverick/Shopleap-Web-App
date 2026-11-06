# Phase 0 baseline

Captured from the existing working tree on 2026-11-04 (Asia/Manila). This is the report-only phase. No application code, dependencies, migrations, or production data were changed. Existing uncommitted work is included in these measurements and was preserved. No commits were created.

## Comparison table

| Measurement | Baseline | Final | Method / limits |
| --- | ---: | --- | --- |
| PHP CLI | 8.5.9 | Pending | `php -v` |
| Laravel | 13.26.1 | Pending | `composer show --direct` |
| Docker PHP | 8.4 | Pending | Existing Dockerfile; differs from local PHP |
| All registered routes | 278 | Pending | `php artisan route:list --json --no-interaction` |
| Routes excluding vendor | 268 | Pending | Same command with `--except-vendor` |
| PHP tests | 96 | Pending | In-memory SQLite; no production database used |
| PHP passing / failures / errors | 77 / 1 / 18 | Pending | JUnit report; 545 assertions; 0 skipped |
| PHP test elapsed | 11.372081 s | Pending | JUnit capture; earlier runs varied; not a benchmark |
| JS tests | 22 / 22 passing when files run separately | Pending | Sandbox blocks default subprocess isolation; see notes |
| Production asset build | Passed | Pending | Vite 8.3.5; reported 2.69 s |
| All build files | 59 / 1,088,979 bytes | Pending | Includes fonts and manifests |
| JavaScript | 44 / 777,102 bytes | Pending | Raw disk sizes, all emitted chunks combined |
| CSS | 7 / 177,221 bytes | Pending | Raw disk sizes |
| Fonts | 6 / 116,068 bytes | Pending | WOFF and WOFF2 |
| Manifests | 2 / 18,588 bytes | Pending | JSON |
| Controllers / Blade files / migrations | 67 / 116 / 89 | Pending | File counts; migrations include pre-existing untracked work |
| Views containing script tags | 18 | Pending | `rg -l '<script(\s|>)' resources/views`; includes external/data scripts, not proof of executable inline JS |
| Mobile LCP / CLS | Not measured | Pending | Requires an actual browser, mobile throttling, and a controlled serving environment |
| Final Docker image size / startup / smoke test | Unavailable | Pending | Docker executable is not installed |

Asset totals describe all build output, not bytes downloaded by any particular page. No performance improvement is claimed.

## Page query baseline

The user confirmed that testing and production share the same database and authorized its use. Profiling used only GET requests, inside explicit PostgreSQL read-only transactions, with `SHOW transaction_read_only` checked before proceeding. Every transaction was rolled back. Sessions/cache used array drivers, mail used the array transport, queues used the null connection, Debugbar was disabled, and application logging used the null channel. No HTTP kernel termination hooks were invoked. No models, accounts, uploads, jobs, or other production records were created or updated.

Each page was handled locally through Laravel's HTTP kernel against the remote PostgreSQL database. Authentication used an existing active, approved account in memory; the buyer was selected by largest cart count, and the product was the first active product. Account/product identifiers and page contents are deliberately not recorded. Account/product selection queries and transaction setup were excluded; role middleware, controller, and view queries were included. Each page used a fresh application and connection. These are single samples, without a production cache warmup or repeated statistical sampling. They establish a reproducible profiling approach, not production latency or browser load metrics. Schema/data changes and selecting a different account/product will affect comparisons.

| Page | HTTP | Query count | Total SQL time (ms) | Local request time (ms) |
| --- | ---: | ---: | ---: | ---: |
| Public home `/` | 200 | 20 | 743.12 | 5714.02 |
| Buyer home `/buyer` | 200 | 45 | 2010.47 | 2119.58 |
| Product list | 200 | 37 | 1676.12 | 2019.81 |
| Product detail | 200 | 51 | 1937.48 | 2940.24 |
| Cart | 200 | 15 | 688.60 | 1087.47 |
| Checkout | 200 | 24 | 1240.20 | 2197.73 |
| Seller dashboard | 200 | 13 | 599.99 | 630.81 |
| Logistics inbound | 200 | 5 | 199.31 | 401.97 |
| Logistics outbound | 200 | 9 | 434.10 | 680.85 |
| Logistics sorting | 200 | 12 | 491.44 | 937.91 |
| Logistics report | 200 | 10 | 415.94 | 463.86 |
| Admin buyer users | 200 | 5 | 227.98 | 256.75 |

## Runtime and middleware

`bootstrap/app.php` uses Laravel's application builder. It loads `routes/web.php`, `routes/api.php`, and `routes/console.php`, and registers `/up` as the framework health endpoint. It trusts all proxy addresses. Middleware aliases are `admin`, `logistics`, `hub.scope`, `seller`, `buyer`, and `courier`. Exception responses use JSON for `api/*` and requests expecting JSON. Default framework web/API middleware is retained; the bootstrap file does not replace those groups.

The buyer and seller middleware require the expected role, an active account, and an approved role profile. Hub scope resolves the signed-in logistics profile; super admins remain unscoped. `AppServiceProvider::boot()` registers two policies and contains no database queries. No explicit production HTTPS forcing was found there.

## Verification findings

- `php artisan test --compact`: 96 tests, 77 passing, 1 assertion failure, 18 errors, 545 assertions. The failing landing-page test receives HTTP 500 because its SQLite database has no `products` table. Other errors include missing `users` tables. `ServiceAreaTest` uses transactions without creating its schema. Historical migrations contain PostgreSQL-specific ALTER statements, so globally enabling SQLite migration refresh is not an established fix; applied migrations must remain unchanged.
- `--no-interaction` is passed through by this test command to PHPUnit, which rejects it. The successful test invocation omitted that unsupported option. The test suite itself still failed as described above.
- The default `node --test tests/Unit/*.test.js` cannot spawn isolated subprocesses in this sandbox (`EPERM`). A diagnostic combined run with `--test-isolation=none` executed 22 tests: 20 passed, 2 failed in `VoucherClaimState.test.js`, including an incomplete `document.querySelector` mock. Running each file in its own Node process with `--test-isolation=none` passed all 22 tests: BuyerAddressState 3, LogisticsReportState 4, NavigationState 12, VoucherClaimState 3. The combined run exposes shared global mock interference; it is not the isolated-file baseline.
- `npm run build` passed. Vite emitted a warning that optimized font fallbacks require optional `fontaine`. No package was installed, removed, or changed.
- Larastan/PHPStan, composer-unused, depcheck/knip, and browser CSS coverage have not been run. No installed PHPStan/composer-unused executable was found. The user requires approval before dependency changes; none were made. Missing tooling is not evidence that a package is unused.

## Deployment inventory and gaps

| Existing item | Observed baseline |
| --- | --- |
| `Dockerfile` | Node 22 Alpine asset stage; PHP 8.4 FPM Debian runtime; nginx and Supervisor; production Composer dependencies; Composer/build dependencies remain in the runtime image; no non-root `USER` |
| `.dockerignore` | Excludes Git, GitHub, env files except example, node_modules, vendor, hot/build output, logs, and tests. Does not exclude the tracked database dump or PHP info dump |
| `docker/entrypoint.sh` | Runtime config, route, and view caching; failures are ignored with warnings; no event cache; startup recursively changes ownership and starts Supervisor |
| `docker/nginx.conf` | Port 10000; gzip; 128 MB request body limit; one-year static caching for both hashed and unhashed assets; no configured security response headers |
| `docker/php.ini` | 12 MB individual upload, 128 MB POST, 120 files, 256 MB memory, 60 s execution, errors sent to stderr |
| `docker/opcache.ini` | OPcache 256 MB, CLI enabled, timestamp checks disabled, 20,000 accelerated files |
| `docker/supervisord.conf` | nginx and PHP-FPM; child output goes to stdout/stderr; no separate queue service |
| FPM pool configuration | No project pool config or explicit worker count found |
| `render.yaml` | Absent |
| `.env.example` | Tracked; contains credential-like values; local/debug defaults; database sessions/cache; synchronous queue; R2 public default disk |
| Database settings | Supabase pooler hostname/5432 in example; SSL required; emulated prepares enabled; explicit persistent PDO option absent; no production connection-limit or memory measurements |
| Persistent Laravel tables | Existing migrations create sessions, cache/cache_locks, jobs/job_batches/failed_jobs; do not add duplicate table migrations |
| Storage | Product images/avatars use `r2_public`; registration documents use private `r2`; these are S3-compatible remote disks. Dynamic attachment disk still needs review |
| Private attachment issue | `config/support.php` inherits `FILESYSTEM_DISK` unless `MESSAGE_ATTACHMENT_DISK` is set. With the example's public default, message uploads inherit public storage. Configure and test private storage before production rollout; changing a default alone does not migrate existing attachments |
| Scheduled tasks | Six commands registered in `routes/web.php`: order completion, cancellation approvals, return deadlines, stale sorting sessions, stale bags, stale parcel events. Scheduler is required |
| Slow mail | `OtpService` uses synchronous mail/notification sending; queue configuration alone will not queue it |
| CI | No `.github/workflows` directory found |

Render configuration should be verified against its official [Docker guide](https://render.com/docs/docker), [Blueprint reference](https://render.com/docs/blueprint-spec), and [Cron Jobs guide](https://render.com/docs/cronjobs). No Render account or running deployment was inspected; repository configuration alone does not establish actual dashboard settings, instance region, plan, worker count, database pool mode, or deployed environment.

## Secrets and tracked artifacts

Credential-like database passwords/URLs, storage access keys, an application key, and a mail password are present in tracked `.env.example`. Git history confirms that file was committed, including commit `2e407c2` dated 2026-10-25. Treat any valid exposed credentials as compromised and rotate them; never reuse them in Render. APP_KEY rotation needs a plan for encrypted application data and existing sessions. Values are intentionally excluded from this report. Removing values in a future commit will not remove their history.

`shopleap.dump` and `phpinfo_dump.txt` are tracked and have history. Their contents were not printed or restored. They require sensitivity and retention review; absence of runtime references does not prove a backup may be deleted. `.env`, vendor, node_modules, and IDE paths checked were not tracked. Tracked storage entries found were `.gitignore` placeholders. This was a targeted check, not a complete historical secret scan, and does not certify the rest of history as clean.

## Deletion report / needs a decision

Nothing was deleted. No unused package, route, class, view, or asset has yet been proven unused.

A literal named-route reference scan excluding `web.php`/`api.php` returned the candidates below. This is deliberately only a candidate list: hardcoded URI requests, string-composed names, external/mobile clients, integration callbacks, notifications, and runtime configuration can still use these endpoints. For example, messaging routes can be called by JavaScript URL paths. The scan does not establish deletion proof.

```text
admin.registrations.buyers.accountStatus
admin.registrations.buyers.approve
admin.registrations.buyers.details
admin.registrations.buyers.reject
admin.registrations.logistics.accountStatus
admin.registrations.logistics.approve
admin.registrations.logistics.details
admin.registrations.logistics.reject
admin.registrations.sellers.accountStatus
admin.registrations.sellers.approve
admin.registrations.sellers.details
admin.registrations.sellers.reject
admin.settings.announcements.update
buyer.account.address
buyer.cart.store
buyer.wishlist.destroy
logistics.inbound.scan.index
logistics.riders.reject
logistics.sorting.sessions.start
logistics.sorting.sessions.end
logistics.sorting.sessions.scan
messages.store
messages.contacts
messages.forward
messages.recent-purchases
messages.widget-data
messages.claim
messages.destroy
messages.details
messages.thread
messages.thread.store
messages.reopen
messages.resolve
messages.unclaim
registration.pending
seller.orders.status
```

Needs a decision or further evidence: backup/PHP info retention; whether valid exposed credentials have been rotated; external/mobile API consumers; local Docker availability; a disposable PostgreSQL database for full-schema tests (the shared production database must not be migrated, truncated, seeded, or used by transactional fixture tests).

## Phase status and change manifest

| Phase | Status |
| --- | --- |
| 0 | Repository/runtime/build/route/test/query baseline recorded; mobile load metrics unavailable |
| 1 | Deployment gaps inspected; implementation pending |
| 2 | Candidate scan and targeted secret exposure findings only; proof-based cleanup pending |
| 3 | Refactoring pending; existing coverage failures recorded |
| 4 | Query/asset baseline available; optimization pending |
| 5 | Targeted findings only; full route/authorization/upload/security audit pending |
| 6 | Baseline checks run; final validation/comparison pending |

Only `phase-0-baseline.md` was added by this audit. The requested build regenerated ignored `public/build` artifacts, tests generated ordinary ignored runtime artifacts, and the JUnit report was written to the OS temporary directory. Existing uncommitted source changes were not modified or committed. A Render environment checklist, deploy/rollback checklist, final configuration artifacts, final comparison, and implementation commits remain pending; this report does not describe the application as production-ready.

## Installed direct dependencies

Exact installed versions follow (not just package.json ranges).

| PHP package | Installed version |
| --- | --- |
| bacon/bacon-qr-code | v3.1.1 |
| barryvdh/laravel-debugbar | v4.4.4 |
| barryvdh/laravel-dompdf | v3.1.2 |
| fakerphp/faker | v1.24.1 |
| laravel/boost | v2.5.5 |
| laravel/framework | v13.26.1 |
| laravel/pail | v1.2.7 |
| laravel/pao | v1.1.4 |
| laravel/pint | v1.30.5 |
| laravel/sanctum | v4.3.3 |
| laravel/tinker | v3.0.2 |
| league/flysystem-aws-s3-v3 | 3.35.3 |
| mockery/mockery | 1.6.14 |
| nunomaduro/collision | v8.9.5 |
| pestphp/pest | v5.1.1 |
| pestphp/pest-plugin-laravel | v5.0.1 |
| picqer/php-barcode-generator | v3.3.0 |
| resend/resend-php | v1.16.0 |

| npm package | Installed version |
| --- | --- |
| @laravel/multiplex | Not installed (optional) |
| @tailwindcss/vite | 4.3.3 |
| alpinejs | 3.17.5 |
| chart.js | 4.6.1 |
| concurrently | 10.0.6 |
| html5-qrcode | 2.3.8 |
| laravel-vite-plugin | 3.2.0 |
| tailwindcss | 4.3.3 |
| vite | 8.3.5 |

## Production build files

Raw byte sizes follow. Vite's largest chunks: scanner 372.53 kB / 110.23 kB gzip; chart auto chunk 203.67 kB / 69.78 kB gzip; app CSS 151.88 kB / 23.65 kB gzip; app JS 61.92 kB / 21.42 kB gzip. No per-page transfer reduction has been measured.


| Build file | Bytes |
| --- | ---: |
| public/build/assets/scanner-CohUoz5C.js | 372534 |
| public/build/assets/auto-gJycSl-K.js | 203672 |
| public/build/assets/app-BfXrFW9N.css | 151880 |
| public/build/assets/app-DIfENC7L.js | 61929 |
| public/build/assets/messaging-BxCk2MxU.js | 23058 |
| public/build/assets/instrument-sans-600-normal-B9e8oLYv.woff | 21676 |
| public/build/assets/instrument-sans-500-normal-Z6ESRlEs.woff | 21652 |
| public/build/assets/instrument-sans-400-normal-D1W7dsQl.woff | 21240 |
| public/build/assets/instrument-sans-600-normal-B7fBEWYG.woff2 | 17408 |
| public/build/assets/instrument-sans-500-normal-Dk9ku72i.woff2 | 17232 |
| public/build/assets/instrument-sans-400-normal-DRC__1Mx.woff2 | 16860 |
| public/build/manifest.json | 12846 |
| public/build/assets/help-CWEmbX8A.js | 12576 |
| public/build/assets/message-widget-BKf4YLrt.js | 11135 |
| public/build/assets/buyer-cart-BRl3m_QF.js | 10957 |
| public/build/assets/navbar-D1FU2VEo.css | 10213 |
| public/build/assets/products-CIzMcCCn.css | 9836 |
| public/build/assets/products-Cgsw97as.js | 7913 |
| public/build/fonts-manifest.json | 5742 |
| public/build/assets/product-show-BGWq-FR7.js | 5264 |
| public/build/assets/orders-Co-McZdh.js | 4626 |
| public/build/assets/navigation-Drhcbf3M.js | 4000 |
| public/build/assets/sorting-BdO_X53i.js | 3924 |
| public/build/assets/seller-register-B_l1R1SO.js | 3843 |
| public/build/assets/logistics-register-COdrtvQz.js | 3551 |
| public/build/assets/buyer-addresses-tEZYcQRw.js | 3456 |
| public/build/assets/registration-review-DvVHaOz7.js | 2971 |
| public/build/assets/service-areas-cKp1H1q5.js | 2892 |
| public/build/assets/terms-modal-DvHakEG-.js | 2839 |
| public/build/assets/admin-dashboard-DHG8-FrL.js | 2734 |
| public/build/assets/dashboard-GNBkHOOX.js | 2406 |
| public/build/assets/fonts-C9MNnjVw.css | 2352 |
| public/build/assets/buyer-register-CZ-nWSsp.js | 1943 |
| public/build/assets/home-DniFhsNE.js | 1938 |
| public/build/assets/landing-C8Mai9OR.js | 1896 |
| public/build/assets/checkout-Dud8yM3Z.js | 1820 |
| public/build/assets/reports-BLT3StjX.js | 1779 |
| public/build/assets/psgcCascade-BS3xJOH8.js | 1755 |
| public/build/assets/order-show-BAH_JlCA.js | 1717 |
| public/build/assets/outbound-show-CCMjSGTZ.js | 1705 |
| public/build/assets/account-DIWB8NFi.js | 1704 |
| public/build/assets/buyerContacts-CV3dxYYB.js | 1695 |
| public/build/assets/order-show-whxrKRRg.css | 1628 |
| public/build/assets/reports-BUOGu0xx.js | 1587 |
| public/build/assets/password-recovery-BAFiduj1.js | 1343 |
| public/build/assets/notifications-Y5iLiwUz.js | 1279 |
| public/build/assets/landing-DZ-vEwph.css | 1278 |
| public/build/assets/buyerSearch-hAjOWw-S.js | 1242 |
| public/build/assets/transactions-DPYqzJxr.js | 1235 |
| public/build/assets/account-Cw1A-Twh.js | 1193 |
| public/build/assets/ui-OzNw8mu0.js | 1102 |
| public/build/assets/vouchers-cAbdbH1H.js | 1092 |
| public/build/assets/login-CJKnMAb3.js | 1059 |
| public/build/assets/inbound-bE6lFQzP.js | 839 |
| public/build/assets/buyer-header-B6DecqVJ.js | 355 |
| public/build/assets/sellers-BpDEna0j.js | 246 |
| public/build/assets/logistics-hdCy-7zA.js | 158 |
| public/build/assets/buyers-BgasiXf0.js | 140 |
| public/build/assets/auth-register-CfwONd4E.css | 34 |

