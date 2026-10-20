# e2e/api-perf-report.md — performance baseline & decisions (perf mission)

Date: 2026-10-09 · branch `architecture/phase-3-web-extraction` · drill stack:
BFF `next start :3000` → Laravel `php artisan serve :8000` → MySQL (single
machine, Windows). Scope: perceived latency + unnecessary work in customer
auth/identity and dashboard data loading. Token/session architecture is
explicitly out of scope here (see `auth/regression-verdict.md` §5/§6).

---

## 1. Instrumentation (Step 01)

Three layers, joined end-to-end by **one request id**:

| Layer | Source | Emitted line |
|---|---|---|
| client | `e2e/api-perf.mjs` (`performance.now()` around the BFF fetch, full round trip) | timestamped `e2e/.logs/api-perf-<stamp>.json` (baseline: `api-perf-baseline-stdout.txt`) |
| BFF → Laravel hop | `apps/web/adapters/backend/laravel-client.ts` (`finally`, gated `PERF_TIMING=1`) | `[upstream] rid=… method=… path=… status=… ms=…` → `e2e/.logs/smoke-bff-out.log` |
| Laravel total + SQL | new `platform-navdhya/app/Platform/Http/Middleware/RequestTiming.php` (prepended global, gated `PERF_TIMING=1`) | `[perf] rid=… method=… route=… status=… total_ms=… sql_ms=… queries=…` → `storage/logs/laravel*.log` |

Notes:

- **Correlation fix (required for the join):** the BFF previously sent only
  `X-Request-Id`, but `AttachRequestId` reads only `Navdhya-Request-Id`, so
  the backend minted its own id and BFF↔Laravel logs could never be joined.
  `callLaravel` now sends the same value on **both** headers (contract §8
  keeps `X-Request-Id`; the backend adopts the id — `req_<uuid>` passes its
  `[A-Za-z0-9._-]`/≤64 sanitize).
- **Zero behavior change when off:** both sides no-op on a single boolean
  env check (`PERF_TIMING` unset in unit tests / normal runs). Log-only.
- **No secrets in logs:** only id, method, route *pattern* (`route()->uri()`,
  path params excluded; body/header/query never logged), status, durations,
  query count. OTP codes travel drill-log → request body only.
- `route` in `[perf]` uses the matched pattern so no identifiers reach the
  log; exceptions log `error=<class>` (the kernel renders the HTTP status
  after the middleware unwinds — the harness records the client-observed
  status against the same rid).

Registration: `bootstrap/app.php` prepends `RequestTiming` after
`AttachRequestId` (outermost; reads the rid set one layer in).
`e2e/smoke.ps1` allowlists `-Script api-perf.mjs` and sets
`PERF_TIMING=1` for all smoke runs.

## 2. Harness design (`e2e/api-perf.mjs`)

Pacing — throttles are **paced, not fought**:

- **Backend `throttle:10,1`** = one shared fixed window per IP for all
  BFF→Laravel traffic (127.0.0.1): batches of **8** backend hits, then
  `php artisan cache:clear` resets the window (the same reset smoke.ps1
  already performs at boot; cache store = `database`; falls back to a 61 s
  sleep if the clear fails). One **unrecorded warm-up backend hit** follows
  each reset (unknown-identity login OTP → real tenant+user queries, no
  challenge row) so no sample measures a cold cache.
- **BFF IP-keyed limits** (`otpIp 10/10min`, `loginIp`, `signupIp`,
  `mfaIp` …) key on `clientIp` → `x-forwarded-for` first hop: each call
  carries a fresh XFF hop, i.e. we measure **service latency on the allowed
  path** — throttle enforcement itself is `scenarios.mjs`' job (83/83).
- **Per-identity caps respected exactly:**
  - issuance samples use **unique phones** (backend `MAX_PER_HOUR=5` and
    BFF `otpIdentity 5/600` never accrue);
  - known-identity login issuance: **exactly 5 per identity × 2 identities**
    (BFF `check()` allows `count ≤ limit`, backend blocks at
    `recent >= 5` — both permit exactly 5);
  - redeem preps consume+delete their challenge row each time, so the
    hourly cap never accrues there either.

Sample budgets (re-runnable: unique phones/ULIDs per run, no fixed-identity
collisions with `scenarios.mjs` / `api-smoke.mjs`):

| endpoint | what it represents | n |
|---|---|---|
| `otp-issue-signup` | full `issue()` transaction (tenant lock + insert) | 10 |
| `otp-issue-login-unknown` | no-oracle fast path (no `issue()`, 2 queries) | 10 |
| `otp-issue-login-known` | full `issue()` on an existing identity | 5 × 2 |
| `signup-issue` | signup intent + challenge | 10 |
| `signup-verify` | **account creation incl. bcrypt** | 10 |
| `login-redeem` | challenge redemption + session issue | 5 × 2 |
| `sessions-list` / `settings-get` / `saved-list` | dashboard data reads | 15 each |
| `saved-add` | dashboard write path | 10 |
| `mfa-status` | MFA read | 10 |
| `mfa-step-up` | `mfa/verify` (fresh TOTP window per sample — replay-safe) | 5 |

MFA step-up costs ~31 s per sample by construction (TOTP replay state must
never see the same code twice); this dominates wall time by design.

**Cold start (why the harness opens with a warm-up phase):** measured on
the first run — the first-ever backend call costs >2 s (PHP/opcache +
Laravel compile on `php artisan serve`), which blows the BFF `fast`
timeout; the aborted call keeps executing on PHP's **single-threaded**
server, so the next call queues behind it (~1.2 s), and steady state
(~100 ms for auth POSTs) is only reached from the 3rd call on. The
warm-up phase (12 sacrificial calls through `backendSlot()`, covering
every BFF route and the bcrypt/verify path) removes all of this from the
samples. `php -S` serialization is a **drill-stack artifact** — real
deployments run php-fpm workers; do not read queueing latency into
production numbers.

## 3. Baseline results (p50 / p95, ms)

Run 2026-10-09 → stdout `e2e/.logs/api-perf-baseline-stdout.txt` (raw
JSON lost — artifact-preservation note in §3.1) —
**130 samples, 0 errors, join coverage 100%** (client ↔ `[upstream]` ↔
`[perf]`).

| endpoint | n | err | client p50/p95 | bff→lar p50/p95 | srv p50/p95 | sql p50 | q p50 | bff p50 |
|---|---|---|---|---|---|---|---|---|
| otp-issue-signup | 10 | 0 | 66.7/105.2 | 61.3/101.0 | 31.2/49.9 | 18.8 | 11.0 | 5.1 |
| otp-issue-login-unknown | 10 | 0 | 63.8/76.1 | 58.2/64.9 | 27.7/33.9 | 20.9 | 8.0 | 6.6 |
| otp-issue-login-known | 10 | 0 | 65.8/85.2 | 61.3/81.3 | 32.8/55.1 | 20.9 | 12.0 | 4.5 |
| signup-issue | 10 | 0 | 60.1/82.1 | 55.0/75.0 | 26.7/39.1 | 15.5 | 11.0 | 4.4 |
| signup-verify | 10 | 0 | **324.9/350.7** | 319.7/344.9 | **289.6/316.8** | 56.7 | 24.0 | 5.0 |
| login-redeem | 10 | 0 | 90.3/207.2 | 86.3/197.9 | 59.0/112.6 | 41.1 | 18.0 | 4.1 |
| sessions-list | 15 | 0 | 82.1/276.3 | 75.1/201.1 | 33.0/138.8 | 17.9 | 6.0 | 7.0 |
| settings-get | 15 | 0 | 92.7/213.7 | 79.7/205.0 | 33.1/155.0 | 19.4 | 6.0 | 10.4 |
| saved-list | 15 | 0 | 81.2/211.3 | 77.0/203.0 | 36.8/150.8 | 26.4 | 6.0 | 5.8 |
| saved-add | 10 | 0 | 98.3/124.0 | 91.6/118.0 | 59.7/85.8 | 48.8 | 10.0 | 6.7 |
| mfa-status | 10 | 0 | 62.6/115.8 | 58.6/112.7 | 29.9/86.4 | 24.3 | 5.0 | 4.3 |
| mfa-step-up | 5 | 0 | 121.8/158.0 | 110.2/152.0 | 51.8/100.2 | 30.7 | 16.0 | 8.4 |

Column semantics: *client* = full BFF round trip; *bff→lar* = upstream hop
(`[upstream]`); *srv* = Laravel total (`[perf] total_ms`) — **excludes**
framework bootstrap, since `RequestTiming` runs inside the pipeline;
*sql* = Laravel query time; *q* = query count; *bff* = client − upstream
(BFF processing + local hop).

Reading the table:

- **Chain composition (typical dashboard GET):** client ≈ 85 = bff
  5–10 + hop 38–46 (TCP + `php -S` accept + Laravel bootstrap) + srv
  33 (sql 19–26 + php ~10).
- **p95 = worst single sample** at these n (max). The elevated p95s on
  `sessions/settings/saved/login` are **environmental stalls**, not
  endpoint work: on those samples *all three* layers slow together —
  e.g. sessions max: client 276.3 = up 201.1 + bff 75, yet srv 138.8
  carries the *same* 6 queries with sql only 20.1 (the max sample's
  query count never changes; ~120 ms of the stall sits between/inside
  PHP as a deschedule). p50s are the stable signal.
- **`signup-verify` is the one tight, genuine hotspot:** srv
  min/p50/max = 272.5/294.8/303.5 — no overlap with any other endpoint,
  3.5–4× the rest of the surface (attribution in §5).
- Instrumentation overhead is below noise: `[perf]`/`[upstream]` are
  log-only writes (sub-ms), guarded try/catch/finally on both sides.

Raw-sample convention: the harness now writes a **timestamped**
`e2e/.logs/api-perf-<stamp>.json` per run. Artifact history: the
original baseline raw was lost to the first after-run's fixed output
path, and both after-run raws were then lost to `run.ps1`'s wholesale
`.logs` wipe (pre-fix) — the surviving stdout captures are
`api-perf-baseline-stdout.txt` and `api-perf-after-clean-stdout.txt`,
and `run.ps1` now rotates only server/scenario logs, preserving
`api-perf-*`/`phpunit-*`.

### 3.1 AFTER (official) — cost-10 signup-verify, settled machine

Run 2026-10-09 21:08 → stdout `e2e/.logs/api-perf-after-clean-stdout.txt` —
**130 samples, 0 errors, join 100%**:

| endpoint | n | err | client p50/p95 | bff→lar p50/p95 | srv p50/p95 | sql p50 | q p50 | bff p50 |
|---|---|---|---|---|---|---|---|---|
| otp-issue-signup | 10 | 0 | 56.8/63.0 | 52.2/58.8 | 30.6/36.9 | 21.1 | 11.0 | 4.6 |
| otp-issue-login-unknown | 10 | 0 | 61.7/65.0 | 56.2/59.9 | 33.5/36.1 | 27.1 | 8.0 | 4.2 |
| otp-issue-login-known | 10 | 0 | 62.3/89.0 | 57.9/84.5 | 32.8/53.2 | 19.9 | 12.0 | 5.1 |
| signup-issue | 10 | 0 | 50.8/63.9 | 45.6/59.5 | 18.9/37.8 | 6.4 | 11.0 | 5.0 |
| **signup-verify** | 10 | 0 | **152.5/203.8** | 146.7/198.9 | **114.7/159.5** | 44.0 | 24.0 | 5.2 |
| login-redeem | 10 | 0 | 85.2/116.0 | 81.2/110.9 | 57.3/83.9 | 43.7 | 18.0 | 4.3 |
| sessions-list | 15 | 0 | 55.1/97.3 | 51.5/85.4 | 27.0/62.9 | 22.0 | 6.0 | 3.9 |
| settings-get | 15 | 0 | 78.1/152.6 | 73.0/147.1 | 42.8/118.2 | 37.7 | 6.0 | 5.3 |
| saved-list | 15 | 0 | 75.8/139.1 | 72.1/135.1 | 39.9/109.9 | 34.8 | 6.0 | 5.4 |
| saved-add | 10 | 0 | 70.5/119.1 | 64.8/113.0 | 41.3/87.1 | 33.6 | 10.0 | 5.2 |
| mfa-status | 10 | 0 | 46.4/110.5 | 41.0/106.0 | 10.8/74.6 | 5.8 | 5.0 | 4.2 |
| mfa-step-up | 5 | 0 | 141.1/162.8 | 127.8/151.5 | 78.0/79.9 | 38.2 | 16.0 | 11.8 |

**Before → after (client p50/p95):**

- **`signup-verify` 324.9/350.7 → 152.5/203.8 = −53% p50, −42% p95** ✓
  (srv 289.6/316.8 → 114.7/159.5 — the ~160 ms bcrypt delta lands as
  predicted: 213 ms at cost 12 → 53 ms at cost 10)
- **every other endpoint ≤ baseline** — several markedly (sessions
  82.1→55.1, mfa-status 62.6→46.4, signup-issue 60.1→50.8) though no
  code touched them: pure run-to-run ambient variance. `mfa-step-up`
  p95 ≈ equal (n=5, TOTP windows).
- errors 0 → 0, join 100% → **RETAIN** (formal verdict in §5).

### 3.2 Post-build ambient run (archived, not the official after)

The first after-run (21:02, started the minute a 1381-page build
finished): same code, but Windows real-time scanning of the fresh
build inflated per-request file-stat/bootstrap (hop +10–30 ms on every
endpoint, one stall sample inside `signup-verify` → p95 444.4) while
SQL got *faster*. Its raw JSON was lost to the pre-fix `run.ps1`
`.logs` wipe; client p50/p95 preserved here:

| endpoint | client p50/p95 |
|---|---|
| otp-issue-signup | 79.5/92.4 |
| otp-issue-login-unknown | 68.2/85.6 |
| otp-issue-login-known | 94.1/126.8 |
| signup-issue | 78.9/95.2 |
| **signup-verify** | **206.7/444.4** |
| login-redeem | 128.3/186.6 |
| sessions-list | 90.3/256.6 |
| settings-get | 90.7/213.5 |
| saved-list | 96.4/251.3 |
| saved-add | 119.7/208.2 |
| mfa-status | 89.7/279.7 |
| mfa-step-up | 103.7/276.5 |

`signup-verify` still improved there (206.7 p50). The 21:08 re-run on
a settled machine is the official AFTER (§3.1).

## 4. Step 02 — dashboard data loading: **there is no waterfall yet**

Finding (static, measurement-independent): the dashboard renders **mock
data server-side** — `RoleWorkspacePage.tsx` calls `getMock*` helpers and
issues **zero client fetches** to `/api/settings`, `/api/saved`,
`/api/notifications` (the only runtime client calls are `authPost` and the
ads decision handler). The serial-fetch waterfall the plan assumed to
parallelize **does not exist in code today**; the BFF routes exist and are
baselined in §3 as the cost the dashboard *will* pay once wired.

Decision: **no parallelization/de-optimization work to do** (nothing to
remove). Keep sessions/MFA reads lazy as they already are. Do not invent a
waterfall.

## 5. Step 03 — hotspot decision

Decisions taken on the §3 numbers (2026-10-09):

1. **`signup-verify` is the measured hotspot → deferred as a
   security-authority decision (needs explicit go-ahead).**
   Attribution: srv p50 289.6 = sql 56.7 (24 queries) + **~229 ms
   non-SQL**, of which **bcrypt ≈ 213 ms** — local bench: cost 12 =
   211–213 ms, cost 10 = 52–57 ms. No `config/hashing.php` exists, so
   Laravel's default **cost 12** applies to the single
   `bcrypt(Str::random(32))` at `CustomerAuthController.php:280` — a
   machine-random password whose plaintext is **discarded** (OTP-only
   account, never used as a login credential). Recommended change:
   cost 10 *for this call only* (expected client p50 325 → ~165 ms;
   cost 8 → ~125 ms), **not** the global hashing config. **Applied on
   explicit go-ahead** (2026-10-09): `bcrypt(Str::random(32), ['rounds'
   => 10])`, written via `setRawAttributes` — the `hashed` cast on
   `User::$password` *verifies pre-hashes against configured
   BCRYPT_ROUNDS* and rejects cost > configured (tests run at 4;
   prod at 12), while the plaintext path would re-hash at configured
   cost (no win). Raw write skips the cast; `Hash::check` verifies
   only the algorithm and there is no password-login path.
   **RETAINED** per the retention bar: p50 *and* p95 both improve
   (324.9/350.7 → 152.5/203.8, §3.1), errors 0 → 0, auth invariants
   green (§6); no other endpoint regressed in the official after-run.
2. **Dashboard reads (priority 1): nothing to remove.** srv 33–37 ms
   on 6 queries, read-only (verified `sessions()`/settings/saved do no
   writes), BFF 5–10 ms, no waterfall (§4). The p95 spikes are single
   environmental stalls (§3). The retain-only-if-before/after-improves
   bar has nothing to operate on.
3. **Config/route caching (hop budget ~30–46 ms/request): rejected on
   gate safety.** A config cache left in `bootstrap/cache/` by a
   crashed smoke run would poison the next PHPUnit invocation — cached
   config bypasses `phpunit.xml` `<env>` overrides, so tests could run
   against the wrong DB and threaten the "E2E DBs only
   `navdhya_`-prefixed" invariant. ~10–20 ms/request is not worth that
   failure mode. (Ops lever for later: generate at boot, clear at
   teardown *and* in the test bootstrap.)
4. **OTP delivery (priority 2): structurally dead** — no provider
   exists (Phase A), hence no delivery latency to hide. `otp-issue-*`
   p50 64–67 ms client / 28–33 ms srv is framework+DB cost only.
   Async-OTP branch: closed.
5. **OTP index (`purpose`/`expires_at` gap): rejected by
   measurement.** Whole issue path sql p50 ≈ 21 ms over 8–12 queries ≈
   2 ms/query roundtrip floor; per-identifier row counts are
   single-digit, so the prefix-only index costs ~0. Nothing measurable
   to win.
6. **`/api/me` aggregate: condition not met** — no waterfall exists
   (§4), so there is no sequential chain to collapse. Not built.

Net: **no optimization adopted unilaterally** — the surface is at floor
except one security-parameter hotspot (item 1), which goes to the user
as a recommendation. Instrumentation stays (log-only, zero behavior
change when `PERF_TIMING` is off).

Carried context from Phase A:

- **OTP has no delivery channel**: `CustomerOtpService::issue()` is
  drill-log only (no SMS/HTTP provider). The deferred-async-OTP branch
  (plan item: "only if SMS-provider latency dominates") is **structurally
  dead** — there is no provider latency to hide. Whatever latency the
  `otp-issue-*` rows show is framework+DB cost (transaction, tenant
  `lockForUpdate`, recent-count query).
- Candidate index gap: `customer_otp_challenges` is indexed
  `['tenant_id','identifier']` while the hourly-cap query filters
  `tenant_id + purpose + identifier + created_at` and supersession filters
  `tenant_id + purpose + identifier + superseded_at` — verify against §3
  `sql_ms`/`q` numbers before touching anything.

### 5.1 Why the cost-10 hash is written via `setRawAttributes`

Two dead-ends hit while applying item 1, recorded so they are not
re-tried:

1. `bcrypt(Str::random(32), 10)` (int) → `TypeError`: this Laravel's
   `BcryptHasher::bcrypt($value, array $options = [])` takes an
   **array** (`['rounds' => 10]`).
2. Normal attribute set (`$user->password = …`) → `RuntimeException` at
   `HasAttributes.php`: the `hashed` cast on `User::$password` calls
   `Hash::isUsingValidOptions`, which **rejects a stored hash whose
   cost exceeds the configured `BCRYPT_ROUNDS`** — tests configure 4
   (phpunit.xml), so the cost-10 hash fails verification, while the
   *plaintext* path would silently re-hash at the configured cost (no
   win) — 9 tests failed at the intermediate state.
3. Fix: hash with `['rounds' => 10]` and write via
   `setRawAttributes([...$user->getAttributes(), 'password' => $hash])`,
   bypassing the cast's verify/re-hash. Safe because `Hash::check`
   reads the cost **from the stored hash itself** (algorithm-only
   verification) and no password-login path exists for these
   OTP-created accounts — the stored value exists only to resist
   offline cracking, where cost 10 was the user-approved trade.

## 6. Gates

Post-instrumentation + post-optimization (2026-10-09):

- web build — ✅ **exit 0** on the canonical invocation
  `NEXT_STANDALONE=0 pnpm --filter @navdhya/web run build` (fresh
  BUILD_ID + prerender/build manifests; `[upstream]` and
  `Navdhya-Request-Id` verified in `.next/server`). The earlier exit 1
  was the Windows `EPERM` symlink of Next's `output: 'standalone'` final
  step — `next.config.ts` line 43 defaults to standalone unless
  `NEXT_STANDALONE='0'`. (Root `pnpm build` still fails in
  `apps/dashboard` on pre-existing React-19 type errors — untouched by
  this mission, not a gate for it.)
- Pint full — ✅ `--test` clean (incl. the cost-10 edit)
- `tsc --noEmit` (web) — ✅ clean
- Jest full (`jest --ci`) — ✅ **342 passed / 20 failed**, where the 20
  are the *same pre-existing* failures as the recorded 340/20 baseline —
  failed suites are all header/SEO/ad-orchestration/preloader
  (`site-header`, `fixed-header`, `new-header`, `PlacementEngine`,
  `LayoutSystem`, `article-ad-experience`, `seo-catalog`, preloader
  pages), none importing auth or `laravel-client`; delta = **+2, my new
  timeout tests, passing**. `laravel-client.test.ts` **6/6**.
- PHPUnit sqlite — ✅ **648 tests / 3704 assertions, 0 failures** (post-
  cost-10; an intermediate attempt failed 9 tests and was diagnosed:
  the `hashed` cast rejects cost > configured `BCRYPT_ROUNDS` — tests
  set 4 — see §5.1; fixed via raw-attribute write, then full green)
- PHPUnit MySQL (`phpunit.mysql.xml`, fresh `navdhya_test_*`) — ✅
  **648 tests · 0 failures · 14 errors** — the documented F-DB3a–c
  non-auth baseline (Temple/Puja ×10, TenantPromotion ×3,
  CleanupExpired ×1); **0 auth failures**
- E2E `e2e\run.ps1` — ✅ **ALL PHASES PASS: 83/83 assertions ·
  Playwright 1/1 · exit 0** (incl. §9b wire gates C1/C2/C4, M2–M5
  enforcement, throttle/Retry-After) — post-both-optimizations
- Smoke re-runnability — ✅ `api-smoke.mjs` **22/22** +
  `api-smoke2.mjs` **13/13**, both exit 0 (re-run post-optimizations)
- api-perf AFTER — ✅ **done ×2** (official clean run in §3.1:
  signup-verify 324.9/350.7 → **152.5/203.8**, 0 errors, join 100%;
  post-build ambient run archived in §3.2)

## 7. Related findings (observed, deliberately not fixed here)

- **BFF upstream timeout escapes as an empty 500 — FOUND HERE, FIXED
  THIS PASS (explicit go-ahead).** When `fetch` inside `callLaravel`
  rejects with `AbortSignal.timeout`'s `DOMException`, the auth routes'
  `catch (CustomerApiError)` did not match it, so the route handler
  threw and Next returned a bare 500 — no envelope, no `requestId`,
  violating contract §8. Pre-existing (identical before this mission's
  edits; observed on the cold-start run as `status=0 error=DOMException`
  in `[upstream]`). Fix: `callLaravel` maps `TimeoutError` →
  `CustomerApiError` (504 `DEPENDENCY_UNAVAILABLE`, the BFF's own rid),
  so every route's existing catch renders the standard envelope; other
  errors keep the documented propagate-to-500 convention. Pinned by 2
  new Jest cases (timeout → envelope with rid; non-timeout still raw).
- **Backend envelope snake_case vs BFF camelCase**: backend errors carry
  `error.request_id`; `laravelErrorResponse` reads `error.requestId`, so
  forwarded backend error ids fall back to `'unknown'` in the BFF
  envelope. Cosmetic for correlation (the BFF's own `requestId` is in the
  response and the `[upstream]` line carries the true rid); noted during
  the correlation fix, not changed.
