# Nowills PMS — Full Codebase Audit

**Date:** 2026-11-11 · **Branch:** `arena/9ce6dd3e-nowillspms` (from `main@8cf63f7`) · **Version:** 7.77.0 (`config/pms.php`)
**Scope:** every tracked file except `build/vendor` (6,519 vendored files) — 2,074 files audited line-by-line with automated scans + manual review of all security-critical paths.
**Method note:** no PHP runtime exists in this sandbox, so PHP was checked with a structural parser (brace/paren balance, namespace-vs-path, duplicate classes, dangerous-function and injection-pattern scans) rather than `php -l`. JS was checked with `node --check` (all pass). CSS brace balance checked. No live server/DB test was possible — findings marked *static* need a runtime confirmation on a staging host.

---

## 1. Executive summary

This is a very large, unusually disciplined codebase for a single-commit project: **~208,000 lines of PHP** (app + config + routes + database), **876 Blade views**, **449 database tables**, **~2,150 routes**. The security architecture is genuinely good in the places that matter most — tenant isolation, permission + licence gating, financial locking, webhook signatures, SSRF guards, and offline idempotency are all thoughtfully implemented, and most of the classic Laravel footguns (SQL injection, mass assignment of `hotel_id`, unescaped output, CSRF gaps, GET mutations) are avoided.

The audit finds **1 critical, 4 high, and a long tail of medium/low issues**. The critical item is the web installer: its "locked" state is only cosmetic — a POST still runs after install. The high items are an SSRF DNS-rebinding gap (including a cloud-metadata bypass), offline snapshot endpoints that leak guest data to any signed-in user, float money arithmetic across the ledger, and the complete absence of tests.

| Area | Verdict |
|---|---|
| Authentication & session | ✅ Strong (lockout, timing equaliser, TOTP + replay guard, session regeneration) — minor gaps |
| Authorisation / tenancy | ✅ Strong (`SetTenant` before binding, `BelongsToHotel` scope, `owned:` validator) — 1 gap (offline snapshots) |
| Financial integrity | ✅ Strong (row locks, void-not-delete, business dates, `decimal` columns) — float arithmetic is the weak point |
| Injection (SQL/XSS/SSRF) | ✅ SQL/XSS clean — SSRF has a rebinding + link-local bypass |
| File upload | ✅ Good validation everywhere — public-dir storage needs hardening |
| Web installer | ❌ **Critical** — lock bypass, no CSRF/auth, world-readable `.env` |
| Tests / CI / static analysis | ❌ **None** — no `tests/`, no CI, no PHPStan |
| Maintainability | ⚠️ God files (routes 3,123 lines, 364 KB view, 2,539-line service), 526 KB README, vendored deps committed |

---

## 2. Inventory (what was audited)

| Area | Size | Notes |
|---|---|---|
| `app/` | 920 PHP files, ~174k lines | 210 controllers, 198 services, 434 models, 33 console commands, 10 middleware, 2 observers |
| `routes/` | `web.php` 3,123 lines / 264 KB, `console.php` 51 lines | ~840 GET, ~1,191 POST, 84 PUT, 43 DELETE, 4 `resource()` |
| `database/` | 146 migrations, 449 `Schema::create` tables, 9 seeder files (~10k lines) | 637 `decimal` columns, 0 `float`/`double` columns |
| `config/` | 17 files | `pms.php` (permissions/menu), `masters.php`, `settings.php` (1,323 lines), `licence.php` |
| `resources/views/` | 876 Blade files | 303 `{!! !!}` unescaped outputs (reviewed — see §6) |
| `public/` | 7 CSS, 12 JS, `sw.js`, images | No build step by design; `app.css` 304 KB, `tv.js` 941 lines |
| `deploy/` | 2 installer templates (661 + 361 lines) | See Critical finding C-1 |
| `docs/` | 24 guides | Good coverage; `README.md` is 526 KB / 2,838 lines |
| `build/vendor/` | 6,519 files, 45 MB, **committed to git** | Laravel 13.33 + 77 prod packages (`composer.lock` present) |
| Root | `.env.example`, `.htaccess` ×2, `composer.json`, `START-HERE.txt`, `CPANEL-INSTALLATION.md` | No `.env` in repo ✅, no `tests/` ❌ |

**Automated checks (all pass unless noted):** real-PHP brace/paren balance 0 mismatches · namespace-vs-path 0 mismatches · `eval`/`shell_exec`/`system`/`passthru`/`unserialize`/`create_function` 0 · `dd`/`dump`/`var_dump` 0 · `$_GET/$_POST` direct 0 · JS `node --check` all pass · CSS braces balanced · `view()` targets 699/699 exist · `route()` targets 2,086 — all resolve (39 apparent misses are `resource()`/multi-line-group false positives, verified manually) · TODO/FIXME 0.

---

## 3. Critical (P0) — fix before any production deploy

### C-1. Web installer lock is cosmetic — anyone can re-run install after setup
**File:** `deploy/installers/public-install.php.txt` (copied to `public/install.php` at install time)

The `installed()` check (`storage/framework/installed.lock`) is only used to choose which HTML block to *display* (line 543). The POST handler at line 248 runs **before** that check and has **no guard at all** — no early `exit`, no auth, no CSRF token, no rate limit. The file also never self-deletes. Consequences for any server where the operator forgets to delete `public/install.php` (the file itself warns this is likely):

- An unauthenticated attacker POSTs `action=install` with their own DB credentials (or the real ones, if known) and the installer **overwrites `.env`**, runs `key:generate` + `migrate --force`, provisions a hotel, and creates a **super-admin** (`firstOrNew` on username — line 428 — so it can also *take over an existing admin* by resetting its password).
- `.env` is written with `chmod 0644` (line 385) — world-readable DB password on shared hosting where other tenants can read it.
- PDO connection errors are echoed verbatim (line 309), disclosing host/DB details.
- `guess_url()` trusts `X-Forwarded-Proto`/`Host` headers for `APP_URL`.

The sibling `root-install.php.txt` *does* exit early when installed (line 35) but is worse in other ways: raw `str_replace` into `.env` with no quoting (a `$` or newline in the DB password corrupts/injects config — the public installer fixed this with `env_quote()`, the root one didn't), no input validation, PDO exception message echoed as JSON, and it writes a `.php` marker file into `storage/framework/`.

**Fix:** add at the very top of both installers (before any POST handling):
```php
if (is_file(LOCK_FILE)) { http_response_code(404); exit; }
```
plus: self-delete on success (`@unlink(__FILE__)`), `chmod 0600` on `.env`, generic DB error messages, and a one-time setup token. Treat any server that ever exposed `install.php` as compromised (rotate `APP_KEY`, DB password, admin passwords).

---

## 4. High (P1)

### H-1. SSRF guard is check-then-fetch (DNS rebinding) and misses link-local addresses
**Files:** `app/Services/IcalService.php:148-172`, `app/Services/EInvoiceService.php:297-304`, `app/Services/WebhookService.php:128-136,301-307`

`assertSafeUrl()` resolves the host with `gethostbynamel()` and rejects private IPs — then `Http::get($url)` **re-resolves** the hostname. An attacker-controlled OTA calendar URL (or GRA E-VAT URL) can return a public IP on first lookup and `169.254.169.254` (cloud metadata) or `127.0.0.1` on the second (classic TOCTOU/rebinding). Two further gaps:

- `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE` does **not** block `169.254.0.0/16` (link-local) — PHP's `NO_RES_RANGE` only covers `240.0.0.0/4` + `255.255.255.255`. Cloud metadata is reachable even without rebinding.
- `gethostbynamel()` is IPv4-only; an IPv6 literal (`::1`, `fe80::…`) yields no IPs and passes the loop vacuously.
- `Http` follows redirects by default, so a public URL can 302 to an internal one.

`WebhookService` is better (it pins the resolved IP with `CURLOPT_RESOLVE` and disables redirects) but shares the link-local/IPv6 gaps.

**Fix:** reuse the webhook approach everywhere — resolve once, pin with `CURLOPT_RESOLVE`, `allow_redirects=false`, block `169.254.0.0/16`, `::1`, `fe80::/10`, and validate IPv6 literals. Never expose the `api_allow_private` / `PMS_CHANNEL_ALLOW_PRIVATE` escape hatch in production.

### H-2. Offline snapshot endpoints leak guest data to any signed-in user
**Files:** `app/Http/Controllers/OfflineSyncController.php` (`posSnapshot`, `frontDeskSnapshot`, `housekeepingSnapshot`, `guestRequestSnapshot`), `app/Services/OfflinePosService.php:52`, `OfflineFrontDeskService.php:45`, `OfflineSyncService.php:37,63`

These routes sit in the `auth+tenant` group with **no `perm:` middleware**, and the services don't gate either — they only *annotate* the payload with `can_order`/`can_checkin`/`can_charge` flags. Any authenticated user (e.g. a housekeeper with no POS access) can call:

- `GET /offline/pos/snapshot` → full menu, running orders, and **all checked-in stays**,
- `GET /offline/frontdesk/snapshot` → arrivals + in-house guests with **names, rates, room numbers** (only the folio *balance* is gated by `folio.view`),
- `GET /offline/housekeeping/snapshot` → all rooms + today's tasks (tasks are filtered for non-managers, rooms are not),
- `GET /offline/guest-requests/snapshot` → stays + open requests (PII-free by design ✅, but still ungated).

The *push* side is correctly permission-checked per operation — only the read side is open. The hub/queue/workspace views are likewise ungated (low risk — they render shells, data comes from the snapshots).

**Fix:** add `perm:` middleware per snapshot (or an explicit `abort_unless` in the service): `pos.view`, `frontdesk.view`, `housekeeping.view`/`attendant`, `requests.view`. Return 403, not an empty payload, so misuse is visible in logs.

### H-3. Money is `float` end-to-end in PHP (columns are `decimal`, arithmetic is not)
**Files:** `app/Services/FolioService.php` (`charge(float $amount)`, `payment(float $amount)`), `LedgerService.php`, `TaxService.php`, `app/Models/Folio.php::totals()`, 1,226 `(float)` money casts, 2,754 `round()` calls

The schema is exemplary (637 `decimal(12,2)`, zero `float` columns), and `round(...,2)` is applied consistently — but every amount is cast to binary float for arithmetic, summed in PHP (`Folio::totals()` sums collections of floats), and compared with tolerances (`close()` allows `abs($balance) > 0.009`). Binary float cannot represent most decimal fractions exactly, so repeated charge/discount/payment cycles can drift by a cent, and the 0.009 tolerance exists precisely because of this. `money()`/`money_short()` also format from float.

**Fix (incremental, no big-bang):** introduce a `Money` value object (integer minor units) at the service boundary — `FolioService::charge(int $minorUnits, …)` — and convert once at the controller edge. At minimum, replace `Folio::totals()` PHP summation with the existing SQL `scopeWithListBalance` pattern so balances are computed in `DECIMAL` in MySQL, and drop the 0.009 tolerance to an exact-zero check once totals are decimal-exact.

### H-4. Zero automated tests for a financial system
No `tests/` directory exists, despite `phpunit/phpunit` in `require-dev` and a `composer test` script that calls `artisan test` (which will fail with "no tests found"). 208k lines of ledger, tax (GRA VAT/NHIL/GETFund/Tourism Levy), payroll (SSNIT/PAYE), night-audit, and channel-sync logic have no regression net. The SQLite function shims in `AppServiceProvider` (FIELD/DATEDIFF/…) suggest someone *intended* to run tests locally.

**Fix:** start with the money paths — `FolioService` charge/payment/void/close, `TaxService` breakdowns, `StayService` check-in/out, `NightAuditService`, `AvailabilityService` overbooking guard, `IcalService` import idempotency. Even 50 focused feature tests would cover the highest-risk logic. Add CI (GitHub Actions: `composer install` + `php artisan test` + Pint + PHPStan level 5).

---

## 5. Medium (P2)

| # | Finding | Evidence | Fix |
|---|---|---|---|
| M-1 | **CSP allows `'unsafe-inline'` for scripts** — inline `<script>` in `layouts/app.blade.php` and ~10 views means the CSP provides no XSS defence-in-depth. | `SecurityHeaders.php`: `script-src 'self' 'unsafe-inline'` | Move inline scripts to files or add per-request nonces; drop `'unsafe-inline'` from `script-src` (keep for `style-src` if needed). |
| M-2 | **Session hardening incomplete** — `SESSION_ENCRYPT=false`, `SESSION_SECURE_COOKIE` commented out in `.env.example`, no idle timeout for staff (partner/organiser portals have one). | `.env.example`, `config/session.php` | Set `SESSION_SECURE_COOKIE=true` + `SESSION_ENCRYPT=true` in prod template; add idle timeout (e.g. 30 min) for staff; consider session fingerprinting. |
| M-3 | **Uploads land in web-accessible `public/uploads/`** with no execution block. Validation is good (`image`+`mimes`+size everywhere, GD re-encode in `RoomPhotoService`), but the GD fallback stores the file *as uploaded*, and `SettingController` logo names use predictable `time()`. | `RoomPhotoService.php:82-88`, `SettingController.php:136`, `IptvController.php:416` | Store uploads under `storage/app/public` (or keep `public/uploads` but add a `.htaccess` denying `*.php` execution + `X-Content-Type-Options: nosniff`); use `random_bytes` filenames everywhere. |
| M-4 | **Cascade deletes on financial tables** — `folios`, `folio_transactions`, `gl_journals`, `supplier_invoices`, `vouchers`, etc. use `cascadeOnDelete()` on `hotel_id`. Deleting/archiving a hotel would silently wipe its ledger history. | Migrations (24 financial tables with cascade) | Change financial FKs to `restrictOnDelete()`; hotels are archived, never deleted (already the policy — enforce it in the schema). |
| M-5 | **No `strict_types`, no static analysis** — 0/1,098 PHP files declare strict types; no PHPStan/Psalm config. Loose comparisons and missing return types are unchecked across 174k lines. | Scan | Enable `declare(strict_types=1)` incrementally (new files first); add PHPStan level 5+ to CI. |
| M-6 | **God files** — `routes/web.php` (3,123 lines), `BriefingService` (2,539), `BudgetService` (1,924), `ReservationDeskService` (1,612), `PosController` (1,502), `reservations/index.blade.php` (364 KB), `README.md` (526 KB). | `wc -l` | Split routes per module (`routes/frontdesk.php`, …); extract services; split README changelog into `CHANGELOG.md`. |
| M-7 | **Stale asset cache-busters** — most assets use `?v={{ config('pms.version') }}` (7.77.0) but `inbox.js?v=73`, `kiosk.js?v=7.2.0`, `tv.js?v=7.1.0` are hardcoded. After an upgrade, kiosks/TVs/inbox may keep running old JS against new APIs. | Views grep | Use `filemtime()` (as `app.css` already does) or the version config everywhere. |
| M-8 | **Unvalidated date/filter inputs** — 251 `$request->input()` calls without validation; date filters use `substr(...,0,10)` + `Carbon::parse` (e.g. `AccountsController`, `ComplianceController:208`, `UtilityController:30`). Garbage input can 500 instead of 422. | Scan | Validate with `date`/`date_format` rules; use `Carbon::createFromFormat` in try/catch or `optional()` parsing. |
| M-9 | **Unbounded list queries** — 73 controllers call `->get()` with no `paginate`/`limit` anywhere in the file (dashboards, reports, exports). Fine at 24 rooms, risky at 500+ rooms / years of history. | Scan | Paginate or cap (`limit(500)`) + export-to-CSV for large sets; add `->chunk()` for exports. |
| M-10 | **E-VAT defaults to sandbox** — `EInvoiceService::driver()` returns `sandbox` unless `gst_einvoice_driver=gra`. A production property that enables E-VAT but forgets the driver silently issues fake IRNs/QRs. | `EInvoiceService.php:37-39` | Default to `gra` when `gst_einvoice_enabled=1`, or hard-block invoice close with a visible warning while on sandbox. |
| M-11 | **MoMo simulator falls back to `APP_KEY` as HMAC secret** — `MoMoGateway.php:88` uses `config('app.key')` when no `momo` secret is set. Anyone who learns `APP_KEY` (e.g. via the installer `.env` leak) can forge payment webhooks. | `MoMoGateway.php` | Refuse to run the simulator when `APP_ENV=production`; never derive webhook secrets from `APP_KEY`. |
| M-12 | **Vendored dependencies committed (45 MB, 6,519 files)** — `.gitignore` ignores `/vendor` but `composer.json` sets `vendor-dir: build/vendor`, so the entire Laravel tree is tracked. Every `composer update` is a 6k-file diff; supply-chain review is impossible in git history. | `git ls-files build/vendor` | Keep the *release zip* vendored (needed for no-Composer cPanel installs) but gitignore `build/vendor` in source; build the zip in CI. At minimum, document that `build/vendor` is generated. |
| M-13 | **Duplicate class basenames** — `ChannelPush` (Command + Model), `RmSnapshot` (Command + Model), `GuestMessage` (Mail + Model). Different namespaces so no fatal, but imports are confusing and IDE/refactor-prone. | Scan | Rename e.g. `ChannelPushCommand`, `RmSnapshotCommand`, `GuestMessageMail`. |
| M-14 | **Demo credentials published** — `Admin@123` / `Pass@123` / `Partner2026` / `Organiser2026` in README + seeders. Mitigated well (seeder throws in production; login-page demo boxes gated by `environment('local')`), but the README table invites seeding a live system with known passwords. | README:2805-2810, `DatabaseSeeder.php:72-81` | Add a bold "never seed production / rotate immediately" banner; generate random demo passwords per seed run. |
| M-15 | **Nginx installs have no vendor protection** — the root `.htaccess` blocks `.env`/`build`/`storage` for Apache, but nginx ignores `.htaccess`. If docroot is mispointed at the project root on nginx, `build/vendor`, `.env`, and `storage/` are web-readable. | `.htaccess`, docs | Add an nginx `location` block to `docs/INSTALL-CPANEL.md` + a CyberPanel guide; add a startup check that warns if `.env` is web-accessible. |

---

## 6. Low / hygiene

- **L-1. `User` model is intentionally unscoped** (group admins cross hotels) with a `visibleToHotel()` scope that must be remembered. One forgotten scope = cross-hotel user leak. Consider a lint rule or a `User::hotelScoped()` default. (`User.php` documents this well — credit for the warning comment.)
- **L-2. `in_array` without `true` in 61 places** (mostly status whitelists) — low risk since values are strings, but strict mode would catch type-juggling bugs.
- **L-3. `Guest::scopeSearch` interpolates `%$term%`** — safe (Eloquent binds), but `%`/`_` in the term act as wildcards. Escape with `addcslashes($term, '%_')`.
- **L-4. `@` error suppression in 33 places** (sockets, `mkdir`, `getimagesize`) — mostly justified, but `@unlink`/`@mkdir` hide permission failures that surface later as confusing errors.
- **L-5. `SettingController` calls `@set_time_limit(300)`** — long-running admin request; ensure the web server timeout is aligned or move to a queued job.
- **L-6. `config/database.php` defaults to SQLite** — `.env.example` correctly sets MySQL, but a bare `cp .env.example .env` on a host that ignores the example gets SQLite. Harmless, but surprising.
- **L-7. `README.md` 526 KB** — the changelog *is* the README. Split into `CHANGELOG.md` + a short README; GitHub renders the first ~1 MB, so this is near the limit.
- **L-8. Single git commit** (`Initial commit`) — no history, no tags, no bisectability. Start committing per-module from now on.
- **L-9. `public/favicon.svg` is 98 KB** — likely an unoptimised export; SVGO it.
- **L-10. `docs/examples/`** — verify no real credentials/tokens are checked in (none found in this audit's secret scan, but re-check after any example update).

---

## 7. What's done well (keep doing this)

These are not boilerplate — each was verified in code:

- **Tenant isolation done right:** `SetTenant` is prepended *before* route-model binding, `BelongsToHotel` adds a global scope + auto-stamps `hotel_id` (which is `$guarded`, never mass-assignable), and the `owned:table` validator enforces hotel ownership on every foreign-key input. Public token surfaces (`/stay/{token}`, `/pay/{token}`, kiosk/TV) resolve the hotel from the token, set the tenant, then re-query scoped — and sub-resources are checked (`myRequest()`, `KioskService::session()` by `kiosk_id`, `IptvService::markRead()` via `messagesFor()`).
- **Authorisation + licensing:** `RequirePermission` checks permission *before* licence (no info leak), `LicenceSurface` runs globally so new public routes are covered by config alone, and the sidebar (`can_do()`) matches the route gate.
- **Financial controls:** `FolioService` locks the folio row (`lockForUpdate`) before every charge/payment/void/close, voids instead of deletes, posts with business dates, and blocks voiding online payments/opening balances. `Numbering::next()` uses `lockForUpdate` on the sequence row — no duplicate invoice numbers. `AvailabilityService` locks inventory to prevent overbooking.
- **Auth:** 5-strike lockout with timing equaliser, session regeneration on login/2FA, TOTP with replay protection (`two_factor_last_step`), recovery codes, admin-only 2FA reset with super-admin guard, `dontFlash` on all secrets, trusted proxies default to loopback (not `*`).
- **Webhooks & APIs:** every payment gateway verifies HMAC with `hash_equals` (Stripe enforces a 300 s replay window), channel API uses timestamp+signature, REST API has per-key rate limits, IP allow-lists, scopes, and full request logging. Stateless routes correctly drop session/cookies/CSRF and add throttles.
- **Uploads:** every upload validates type + size + mime; guest ID docs and signatures go to the private `local` disk with `no-store` + sandboxed CSP on serve; room photos are GD re-encoded.
- **XSS:** Blade escapes by default; all 303 `{!! !!}` uses were reviewed — `status_badge()` escapes, `nl2br(e(...))` escapes, `SopDocument::render()` uses `Str::markdown(..., html_input: escape)`, TV boot JSON uses `JSON_HEX_*` flags, and every JS `innerHTML` site escapes via `esc()`/`escapeHtml()`.
- **CSRF:** all 1,634 forms checked — every POST form has `@csrf`; the ~100 forms without it are all `method="get"` filters. No GET route mutates state.
- **Offline design:** service worker caches only designed offline surfaces + static assets, never `no-store` responses or POSTs; offline pushes are idempotent with server receipts and conflict reporting; money actions (payments/checkout) stay online-only by design.
- **Migrations:** all 146 have `down()`, money is `decimal` everywhere, FKs are constrained, indexes/uniques are thorough. The seeder refuses to run in production; demo login boxes are `local`-only.
- **PII:** guest ID/passport/DOB are masked by default with a logged, reason-required reveal flow (`pii.reveal`); privacy erasure/restriction is enforced in controllers; offline snapshots deliberately omit guest PII (guest-requests slice).

---

## 8. Remediation roadmap (suggested order)

1. **This week:** C-1 installer lock + `.env` perms; H-1 SSRF pinning + link-local block; H-2 snapshot `perm:` gates. (All small, surgical changes.)
2. **Next sprint:** H-3 decimal money at the folio boundary; M-1 CSP nonces; M-2 session hardening; M-3 upload `.htaccess`; M-10 E-VAT sandbox guard.
3. **Next month:** H-4 test harness + first 50 financial tests + CI; M-5 PHPStan; M-6 route/service splits; M-12 vendor gitignore + CI-built release zip.
4. **Ongoing:** M-7 asset versioning, M-8 input validation sweep, M-9 pagination caps, L-1…L-10 hygiene.

---

## 9. Appendix — per-directory notes

**`app/Http/Controllers/` (210 files):** Consistent `validate()` + `attempt()` + `ActivityLogger` pattern. 141/210 call `validate()` directly; the rest delegate to services that validate field-by-field (verified for `BusinessDevService`). No controller uses `eval`, raw superglobals, or `DB::raw` with user input. `MasterController` is a model citizen (config allow-list + `can_do` per master). `BriefingController` enforces per-pack permissions in every action despite having no route middleware.

**`app/Services/` (198 files):** The business logic lives here, not in controllers — good. `DB::transaction` in 115 services. Largest files (`BriefingService` 2,539 lines, `BudgetService` 1,924) mix calculation, persistence, and formatting — split candidates.

**`app/Models/` (434 files):** Uniform `$guarded = ['id','hotel_id']`, `BelongsToHotel` trait, `decimal:2` money casts. No `SoftDeletes` (deletion is hard, but financial records are voided, not deleted — correct for a PMS). `User` is the deliberate exception (see L-1).

**`app/Http/Middleware/` (10 files):** All reviewed. `ApiAuthenticate` (114 lines) is thorough. `SecurityHeaders` is good except `unsafe-inline` (M-1). `SetTenant` correctly handles suspended/archived properties by moving the user instead of erroring.

**`routes/web.php`:** 2,153 named routes, all `route()` calls resolve, no full-URI duplicates, static-before-wildcard ordering with `whereNumber` constraints throughout, `perm:` on every authenticated route except the 35 documented in §4–§5 (profile/2FA/search/notifications/offline/briefings/masters — each verified to enforce in-controller). Public routes are all token-gated + throttled.

**`database/`:** 449 tables, exemplary money/FK/index discipline. Line-item tables without `hotel_id` are correctly scoped via parents. One `softDeletes` (non-financial). Seeder is production-guarded.

**`config/`:** Secrets via `env()` only; `dontFlash` covers gateway/SMS secrets; settings registry (`settings.php` + `setting-defaults/fallbacks/readers`) is unusually well-documented with a change trail (`SettingValueObserver` masks secrets).

**`resources/views/` (876 files):** Escaping discipline verified. `layouts/app.blade.php` builds the notification centre per request (permission-scoped, try/catch-isolated — good, but watch its query cost on large hotels). Print layouts are separate and light.

**`public/js/` (12 files) + `sw.js`:** Vanilla, no dependencies, no `eval`. All `innerHTML` sites escape. `inbox.js` inserts server-rendered `m.html` — safe only if the server escapes message bodies (verify `InboxController` poll HTML). `tv.js` calls open-meteo directly from the TV (by design, no key needed).

**`public/css/` (7 files):** Single-file no-build design; `app.css` 304 KB with `filemtime` busting. Braces balanced.

**`deploy/`:** See C-1. The `.php.txt` template pattern (never executable in place) is smart; the execution is not.

**`docs/` + root:** Install guides are thorough (cPanel + CyberPanel). `START-HERE.txt` matches the installer flow. `README.md` needs splitting (L-7).

**`build/vendor/`:** Laravel 13.33, Guzzle 8.2, CommonMark 2.10 — current. Committed to git (M-12). No known-vulnerable versions spotted in the lockfile top-level list, but run `composer audit` in CI to be sure.

---

*Audit performed statically on branch `arena/9ce6dd3e-nowillspms`. No files were modified. Runtime behaviour (query performance, actual HTTP responses, installer execution) was not tested — confirm C-1/H-1/H-2 fixes on staging before production.*
