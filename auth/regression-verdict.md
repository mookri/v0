# Auth/MFA full-stack regression — verdict report

Date: 2026-10-09 · Branch: `architecture/phase-3-web-extraction` @ `ae6a960`
(uncommitted working tree — see §8) · Environment: PHP 8.5.6, Node 24.14.1,
pnpm 9.15.4, MySQL 8.0.44 (localhost:3306).

Stack under test: **Next.js BFF** (`apps/web`, `next start :3000`) →
**Laravel** (`platform-navdhya`, `php artisan serve :8000`) → **MySQL**.
Drivers: HTTP cookie-jar scenarios (`e2e/scenarios.mjs`, S1–S13) and
Playwright UI (`e2e/ui.spec.ts`), orchestrated by `e2e/run.ps1`.

---

## 1. Gate status

| Gate | How | Result |
|---|---|---|
| Build | `NEXT_STANDALONE=0 pnpm --filter @navdhya/web run build` | ✅ green — re-run 2026-10-10 (F1/F2/B1: healthz/readyz/pujas routes, QuickBook handoff) |
| Typecheck | `pnpm --filter @navdhya/web run typecheck` (+ customer) | ✅ green (both apps) — re-run 2026-10-10 |
| Jest (BFF contract units) | `pnpm --filter @navdhya/web run test` | ✅ **353 passed / 20 failed** — 20 remain the pre-existing failures in the same 9 non-auth suites; **0 auth failures**; delta **+11** = this cycle's new healthz/readyz/pujas/QuickBook tests; root `vitest run tests/customer` **69/69** |
| Pint (full suite) | `vendor/bin/pint --test` in `platform-navdhya` | ✅ clean — re-run 2026-10-10 (outbox + public catalog) |
| PHPUnit (sqlite) | `vendor/bin/phpunit` in `platform-navdhya` | ✅ **663 tests / 3762 assertions — 0 failures, 0 errors** (2026-10-10; +14 = OutboxTest 6 + PublicCatalogTest 4 + prior) |
| PHPUnit-on-MySQL | `vendor/bin/phpunit -c phpunit.mysql.xml` against fresh `navdhya_test_*` | ✅ **663 tests · 0 failures · 14 errors** — same documented non-auth baseline F-DB3a–c (TenantPromotion ×3, CleanupExpired ×1, TempleCursor/Puja ×10); **0 failures in new tests** (2026-10-10, fresh DB incl. `2026_10_09_000003_create_outbox_events_table`) |
| E2E HTTP (S1–S13) | `e2e/run.ps1` phase 1 | ✅ **83/83 assertions · 13/13 scenarios · exit 0** — incl. §9b wire gates C1/C2/C4 (S13) and G1–G4 (S9/S10/S11); re-run 2026-10-10 00:27–00:33 (migration applies outbox table on fresh DB) |
| E2E Playwright (UI) | `e2e/run.ps1` phase 2 (BFF restarted, `cache:clear`) | ✅ exit 0 (1/1) — signup → 3-attempt exhaustion → login → **UI logout**; re-run 2026-10-10 |
| API surface smoke (non-auth) | `e2e/smoke.ps1` (+ `-Script api-smoke2.mjs`) | ✅ 2026-10-10 00:34–00:35: campaign **22/22** · probes **13/13** — **F-SMOKE1..5 all fixed** |
| Perf before/after (retention bar) | `e2e/smoke.ps1 -Script api-perf.mjs` | ✅ 2026-10-10 00:36–00:39: **130 samples · 0 errors · join 100% · all budgets met**; every endpoint within ambient variance of the official AFTER in `e2e/api-perf-report.md` §3.1 (signup-verify 155.5/199.5 vs 152.5/203.8 — cost-10 win retained; several endpoints faster) |

Latest full pipeline: 15:18:44 → 15:24:49 (~6.1 min, 2026-10-09,
**post-F-SMOKE1..4 fixes**: HTTP 83/83 + Playwright exit 0). A first
post-fix attempt (15:08) also passed HTTP 83/83 but the Playwright
phase tripped a navigation race at `/login` (the shell's own
post-logout routing vs the test's explicit `goto` — snapshot showed
`/login` already rendered); `e2e/ui.spec.ts` now tolerates that race
and the re-run above is green. Earlier in the mission, an attempt
started right after a machine reboot hit BFF fetch timeouts (`fast` 2 s
/ `normal` 8 s) against a cold single-threaded `artisan serve` (`/up`
61 s, `enroll` 20 s) — environmental only: every backend assertion that
completed was correct, and the warm re-run was 83/83 (see F-E2E5).

Post-perf-mission full re-run (2026-10-09 21:12 → 21:25, after the two
approved optimizations — cost-10 signup-verify bcrypt, BFF timeout→504
envelope): build ✓ (×2) · typecheck ✓ · Pint ✓ · Jest 342/20 ✓ ·
PHPUnit sqlite 648 ✓ · MySQL 648/0-failures/14-documented ✓ ·
`run.ps1` 83/83 + Playwright 1/1 **ALL PHASES PASS** ✓ · smokes 22/22 +
13/13 ✓ — same green state as the pre-change baseline; perf numbers and
the retention verdict live in `e2e/api-perf-report.md`. Evidence note:
`run.ps1` used to wipe `e2e/.logs` wholesale at start (it deleted the
first after-run's perf JSONs); it now rotates only server/scenario logs
and preserves `api-perf-*`/`phpunit-*` artifacts.

F-SMOKE5 backend 409 guard landed next (2026-10-09 evening); gates
re-run: Pint ✓ · sqlite **649**/3709 ✓ · MySQL 649/0 failures/14
documented ✓ · campaign 22/22 + probes 13/13 ✓ (see §5).

F1/F2/B1 cycle (2026-10-10 00:27 → 00:39, full gate cadence green):
web build ✓ · web+customer typecheck ✓ · Pint ✓ · sqlite **663/3762**
0 failures ✓ · MySQL **663 · 0 failures · 14 documented errors** (fresh
`navdhya_test_*`, outbox migration applies) ✓ · web Jest **353** passed
(+11 new: healthz/readyz/pujas routes + QuickBook handoff; same 20
pre-existing failures in the same 9 suites) ✓ · customer vitest 69/69 ✓
· `run.ps1` **83/83 + Playwright 1/1 ALL PHASES PASS** exit 0 ✓ ·
smokes **22/22 + 13/13** ✓ · perf 130 samples 0 errors all budgets met
(no regression) ✓. New scope this cycle: F1 ops probes (BFF
`/api/healthz`/`/api/readyz` + runbook), F2 transactional outbox
(`outbox_events` on tenant_shared, `outbox:drain`, 6 unit tests), B1
public catalog (`GET /api/v1/catalog/public/items` anonymous + BFF
forward + QuickBook real-catalog handoff, 4 backend + 3 web tests).

---

## 2. Verdict matrix — scenario × wire × db × contract

Latest run (2026-10-09, post-M2–M5), verbatim (`83/83 PASS · 0 FAIL`):

| Scenario | wire | db | contract | notes |
|---|---|---|---|---|
| S1 | PASS | PASS | PASS | ✓ status=202 challengeId ok-envelope<br>✓ expiresIn=600 (V2)<br>✓ attempts=0 row<br>✓ TTL created_at→expires_at = 600 s (V2)<br>✓ §1/§4 challenge issued once |
| S2 | PASS | PASS | PASS | ✓ replay 202, same challengeId<br>✓ different body → 409 CONFLICT<br>✓ active challenges for A = 1<br>✓ §4 Key+replay, conflict→409 |
| S3 | PASS | – | PASS | ✓ 401 UNAUTHENTICATED<br>✓ no existence oracle ("Invalid credentials.")<br>✓ §9a V4 uniform 401 |
| S4 | PASS | PASS | PASS | ✓ wrong code ×3 → 401 each, attempts 1→2→3<br>✓ row survives 3 failures (count=1)<br>✓ 4th call deletes row (count=0)<br>✓ same code as unknown-challenge probe (V4)<br>✓ **C4/M3 enforced** — attempts counted exactly under row lock; §9a + §9b gate |
| S5 | PASS | PASS | PASS | ✓ signup 202 → redeem 201 + cookie + sessionId<br>✓ users row phone A (count=1)<br>✓ challenge deleted on consume (count=0)<br>✓ §2/C1 — redeem consumes the challenge atomically (M3) |
| S6 | PASS | PASS | PASS | ✓ modern `{channel,value}` → 202 + `challenge_id`<br>✓ challenge row created (0→1)<br>✓ own verified contact → documented 409 CONFLICT<br>✓ legacy `{type,contact}` → 422 VALIDATION_ERROR<br>✓ **M1 fix** proven both directions |
| S7 | PASS | – | PASS | ✓ cookie session → 200, data is array<br>✓ no cookie → 401 UNAUTHENTICATED<br>✓ invalid cookie → 401, **no header fallback**<br>✓ §7 cookie-first precedence |
| S8 | PASS | – | PASS | ✓ otp 202<br>✓ login redeem 200, cookie set, `receivedForm=proof`<br>✓ §2 `proof` canonical |
| S10 | PASS | PASS | PASS | ✓ unknown-identity login → identical 202 decoy<br>✓ decoy persisted: 0 rows<br>✓ decoy verify → 401 (can never verify)<br>✓ B provisioned 202→201<br>✓ two distinct challenges, then **total=2 active=1** (supersession; rows retained for the cap)<br>✓ **C2/G1 — M2 enforced**: exactly one active challenge per (tenant, purpose, identifier)<br>✓ superseded challenge redeem → 401 (no supersession oracle) |
| S11 | PASS | PASS | PASS | ✓ status w/o cookie 401<br>✓ enroll 201 otpauth URI (AAL1-only — §5 errata, optional hardening still open)<br>✓ confirm 200, 10 recovery codes, `users.mfa_secret` stored<br>✓ step-up verify 200, `mfa_step_ups` 0→1 (§9a V8)<br>✓ **rotation Set-Cookie adopted** by the BFF<br>✓ **G3**: predecessor token dead direct-on-Laravel (401), rotated bearer alive (200)<br>✓ recovery-code revoke: BFF 422 schema / backend direct 401 (V7)<br>✓ wrong TOTP → 401 (**failure counted toward the M4 lockout**)<br>✓ 6th MFA POST → 429 + Retry-After (§9a V6, BFF 5/900)<br>✓ recovery code login 200, reuse 401 — single-use (V7)<br>✓ **G2**: 5 direct wrong codes → 429 + Retry-After=900; correct code while locked → 429 (M4 enforced) |
| S9 | PASS | PASS | PASS | ✓ logout 200, `Set-Cookie … Max-Age=0` clears cookie<br>✓ post-logout cookie read → 401<br>✓ **G4 — M5 enforced**: bearer dead direct-on-Laravel (401), `actor_sessions.revoked_at` 1→2<br>✓ `audit_logs` `customers.auth.logged_out` 0→1<br>✓ revocation store is the logout; cookie clearing is the client half |
| S13 | PASS | PASS | – | ✓ **C2** overlapping resends → 202/202, 2 distinct challenges, **active=1 total=4** (superseded rows retained)<br>✓ **C1** overlapping redeems → 200/401 (exactly one winner, one uniform 401), challenge deleted<br>✓ **C4** 3 overlapping wrong codes → 401/401/401, **attempts exactly 3** (no lost updates) |
| S12 | PASS | – | PASS | ✓ tripped=true, `RATE_LIMITED`, Retry-After<br>✓ §8 signup IP throttle 5/600 → 429 + Retry-After |

---

## 3. Split gate — §9a current-system (passes today)

Every §9a invariant is proven green in the latest run (83/83):

| ID | Gate | Proven by |
|---|---|---|
| V1 | Verification start sends `{channel,value}`, never `{type,contact}` | S6 (202 + row; legacy → 422 at BFF) |
| V2 | `expiresIn` surfaces backend 600 s TTL | S1 (`expiresIn=600`, DB TTL 600 s) |
| V3 | 3 failures exhaust UX challenge → restart, no fake lock | S4 (attempts 1→2→3, row survives, 4th deletes) |
| V4 | Unknown/invalid challengeId → single uniform 401, no oracle | S3, S4, S10 |
| V5 | Logout always clears cookie; cookie/bearer semantics hold | S9 (+ S7 cookie precedence) |
| V6 | MFA throttle BFF `5/900` + backend `10/1` | S11 (429 + Retry-After) |
| V7 | Recovery codes single-use; cannot revoke | S11 (reuse 401; revoke refused) |
| V8 | Step-up proof produced; 15-min freshness = backend `RequiresAcr::FRESH_MINUTES` | S11 (`mfa_step_ups` row) |
| V9 | No test asserts behavior the backend does not have | S4/S9/S10/S11/S13 assert exactly the backend's real behavior (§4 records the M2–M5 flips) |

## 4. Split gate — §9b security-improvement (landed — re-run green)

`auth/backend-security-gaps.md` — the BFF implements **none** of these
deliberately (a hardened BFF over a weak backend would be a false
boundary); all four are now backend state in `platform-navdhya`:

| Ticket | Severity | Gate in §9b | Status |
|---|---|---|---|
| M3 atomic challenge redemption | critical (double session) | C1, C4 | ✅ fixed — txn + `lockForUpdate`; S13 (200/401, attempts=3), `CustomerOtpSupersessionTest` |
| M5 session revocation / rotation (TOKEN-001) | critical (logout cosmetic) | G3, G4 | ✅ fixed — `actor_sessions` store + rotation; S9 (bearer 401, revoked 1→2), S11 (G3 401/200), `ActorSessionRevocationTest` |
| M2 challenge supersession on resend | high (dual-OTP window) | C2 / G1 | ✅ fixed — `superseded_at` in issuing txn; S10 (active=1, redeem 401), S13 (active=1) |
| M4 MFA attempt lockout + TOTP replay state | high (6-digit space) | G2 | ✅ fixed — 5/900 s lock + used-step state; S11 direct drill (429 + Retry-After=900, locked correct-code 429), `MfaLockoutTest` |

Sequence completed as specified: fixes landed → §9b re-run (**83/83**,
13/13 scenarios, Playwright exit 0, 2026-10-09) → `p0-contract.md`
invariant tags flipped from `NOT ENFORCED` → `backend-security-gaps.md`
and `laravel-contract.md` truth-upped.

---

## 5. Findings register

### Fixed this cycle — BFF (`apps/web`, uncommitted)

| ID | Finding | Evidence | Fix |
|---|---|---|---|
| M1 | Verification start wired as `{type,contact}` — hard mismatch with backend `$request->validate` | `laravel-contract.md` L73 | `{channel,value}` discriminated union; legacy → 422 (S6 both directions) |
| M6 | BFF claimed a fake 15-min OTP UX lock the backend does not have | `p0-contract.md` L70–73 | lock claim removed; exhaustion = row delete (S4 documents reality) |
| M9 | Backend `step_up_required` fell through to opaque `INTERNAL_ERROR` | Jest: *step_up_required maps to FORBIDDEN, not opaque INTERNAL_ERROR (M9)* | mapped to `FORBIDDEN` + test |
| M10 | `NAVDHYA_API_BASE` with `/api/v1` suffix double-prefixes → 404 | `laravel-contract.md` L163 | bare-origin contract documented (integration note) |
| M6b | Retry-After relay: BFF fabricated/lost backend `Retry-After` (contract §8: pass-through only) | — | pure pass-through + 4 new Jest tests |

### Fixed this cycle — E2E harness (`e2e/`, uncommitted)

| ID | Finding | Root cause | Fix |
|---|---|---|---|
| F-E2E1 | Run-3 S1 → empty 500, cascading S2/S4 (10 of 13 failures) | PHP cold route-compile on the first auth POST ≈ 3 s > BFF `fast` (2 s) → `callLaravel` `TimeoutError` (code 23) → not a `CustomerApiError` → `laravelErrorResponse` rethrows → framework 500 with empty body (documented BFF convention: no explicit 500 envelopes). Evidence: run-3 `bff1-err.log`, laravel access log `signup ~3 s` | `run.ps1` issues **one direct backend warm-up signup** (bypasses BFF timeout classes) after both servers are ready; +1 of 10 slots absorbed by pacing (8 paced + 1 warm ≤ 10; backend window opens earlier than `thHit()`'s first sleep, so it decays first). Run 4: warm 202 → S1 202 |
| F-E2E2 | Run-3 S6/S7 authenticated with an **empty cookie jar** → BFF-local 401, no backend access-log line | S5's verify call omitted `jar:` — the verify-issued `Set-Cookie` was asserted on the response but never captured. (Run-3's lone `/identity/sessions` backend line was S7's **garbage** probe: `extractSessionToken` does no value validation, so `x×48` was forwarded and the backend correctly 401'd it — verify-issued tokens are fine: run 4 S7 → 200) | `jar: jarA` on S5 redeem; comment explains why |
| F-E2E3 | S6's own expectations contradicted documented backend truth | `request()` answers **409 for the actor's own already-verified contact** (documented in `laravel-contract.md` L73); success keys are backend pass-through `challenge_id`/`expires_in`, not camelCase `challengeId`; PHONE_A is verified by S5 | S6 reworked: fresh value → 202 happy path + row assert; separate documented-409 assertion for own verified contact; legacy 422 unchanged |
| F-E2E5 | Post-M2–M5 attempt on a freshly rebooted machine: 6 failures (S10 redeem, S11 enroll, S13 C2/C1), all 500s | Cold system → `/up 61 s`, `/health 8 s`, `enroll 20 s` hogged the single-threaded `artisan serve`; BFF fetch timeouts (`fast` 2 s / `normal` 8 s) fired → `TimeoutError` (code 23) → framework 500. Subtle cascade: C2's timed-out resend **kept executing server-side** and superseded the counted challenge *after* the assertion read — so C1's redeems hit a superseded row → uniform 401/401. Every backend assertion that completed was correct | Environmental, not logic: warm re-run → **83/83**. Documented here so a cold-boot 500 is diagnosed as fetch-timeout, not a backend regression |
| F-E2E6 | BFF refused to boot: `next start does not work with "output: standalone"` | `run.ps1` set `NEXT_STANDALONE=0` only inside the build-missing branch, but `next.config.ts` re-evaluates `output` at **runtime** — a pre-built non-standalone `.next` still booted a standalone config when the env var wasn't inherited | `$env:NEXT_STANDALONE='0'` set unconditionally at the script head (covers build guard + `next start`) |
| F-UI1 | Header dropdown **Logout button inert** (no handler) — found by first Playwright run | `DashboardShell` | wired to existing `POST /api/auth/logout`; proven in UI phase (README §UI logout) |

Run 3's 13 failures are fully accounted for: S1 ×4 (F-E2E1), S2 ×1 + S4 ×5
(S1 cascade), S6 ×2 (F-E2E2 + F-E2E3), S7 ×1 (F-E2E2). Run 4: **0**.

### Documented dead-end (not a bug to fix in the BFF)

**`POST /api/auth/otp purpose=signup` can never be redeemed.**
`requestOtp` accepts `purpose ∈ {login, signup}` (`CustomerAuthController`
L134) and issues the challenge **without a payload** (L160–165), but
`verifySignup` requires `payload.phone` (L258–259) → `$phone === ''` →
deterministic **409** "An account already exists for this phone number.
Use login." (L261–264) — a misleading message for a challenge that never
carried an account intent. The only supported signup path is
`signup → verify` (as S5 does). BFF relays both honestly; no BFF-side
guard exists by design (Laravel is the security authority).

Related no-oracle behavior (correct, asserted by S10): unknown **login**
identities get an identical 202 with a fabricated ULID challenge id that
is never persisted (L144–157) and can never verify (401).

### MySQL gate findings (`platform-navdhya`, `phpunit.mysql.xml`)

- **F-DB1 — `migrate:fresh` never wipes the non-default connection**
  (run precondition): `migrate:fresh`/`db:wipe` operate on the default
  (`central`) connection only, while tenant tables are created by
  migrations that declare `Schema::connection('tenant_shared')` — so
  leftover `tenant_shared` tables from a previous run fail migration
  `2026_08_28_000003` (`Table 'audit_logs' already exists`) before any
  test body runs. Protocol: drop/recreate **both** `navdhya_test_*`
  before each run (exact SQL in the `phpunit.mysql.xml` header).
  Post-F-DB2 the suite also self-heals this on its first database test
  (proven: run 7 ran against run 6's dirty state with **no** reset and
  reproduced it exactly), but drop/recreate remains the documented,
  deterministic baseline.
- **F-DB2 — mid-suite guard flip → doomed re-migrate → 199 identical
  errors (fixed this cycle)**: `TenantAdoptLegacyTest` creates a scratch
  `legacy_notes` table (DDL) inside the wrapped transaction; MySQL DDL
  implicitly commits, so at teardown `RefreshDatabase`'s
  `beforeApplicationDestroyed` guard sees `!inTransaction()` and resets
  `RefreshDatabaseState::$migrated = false` (vendor
  `RefreshDatabase.php:159`). The next test re-runs `migrate:fresh`
  (wipes `central` only) and dies on `tenant_shared.audit_logs` — the
  exception fires **before** `$migrated = true` can be set, so every
  subsequent DB test repeats it: the prior full run (11:34, 1905
  assertions) and run 5 (9:44) both ended with **199 byte-identical
  errors / 398 "already exists" lines** — run 5 started from
  verified-empty DBs, proving it is not stale state (the rejected
  hypothesis). Fix (test file):
  `TenantIntegrationTestCase::migrateDatabases()` wipes `tenant_shared`
  (`dropAllTables()`, MySQL only — the sqlite `:memory:` baseline never
  trips the guard because sqlite DDL is transactional) before delegating
  to `RefreshDatabase`'s `migrate:fresh`, so the vendor recovery path
  reaches first-run state on **both** kernel connections. Run 6:
  **0 × SQLSTATE 42S01 · 199 → 14 errors · 11:34/9:44 → 1:47**; run 7
  (dirty DBs, no reset) identical → deterministic. Architecture
  collateral cleared with it (see below).
- **F-DB3 — first MySQL baseline: 14 non-auth SQLite-vs-MySQL
  portability errors** (all green on `:memory:` sqlite; app/factory code
  read-only this cycle → backend tickets, §7 item 7):
  - **F-DB3a — 3 × `TenantPromotionTest`**: `tenant:provision-schema`
    derives `$driver` from the **target** connection
    (`TenantProvisionSchemaCommand.php:41`) but reads DDL from the
    **source** (`tenant_shared`); with the test's intentional sqlite
    promotion target it runs `SELECT sql FROM sqlite_master` on MySQL →
    1064. Fix: derive the driver from `$source`.
  - **F-DB3b — 1 × `CleanupExpiredTest`**: `otp_challenges.expires_at`
    is `timestamp` (2038 ceiling) and the test seeds `2099-01-01` →
    SQLSTATE 22007. Same latent column class: `invites.expires_at`,
    `api_keys.expires_at`. Fix: `dateTime` in the migrations, or seed
    ≤ 2038 in the test.
  - **F-DB3c — 10 × `TempleCursorTest` (3) + `TemplePujaSurfaceTest`
    (7)**: `TemplePujaFactory` seeds `puja_id => fake()->uuid()` (36
    chars) into `$table->ulid('puja_id')` → `char(26)`
    (`2026_10_05_093001_create_temple_pujas_table.php`) → strict-mode
    1406. Fix: `Str::ulid()` in the factory (or a per-test override).
- **Pre-existing Architecture failures**: none. Run 5's three
  (`PlatformGuardsTest` ×2, `QueryPlanTest`) were byte-identical F-DB2
  collateral (`audit_logs already exists`); run 6 → Architecture suite
  fully green.

### Pre-existing, out of scope

- **Jest baseline** (re-run this cycle, post-F-SMOKE fixes): `9 failed,
  33 passed suites · 20 failed, 340 passed tests` — 340 = the 339
  baseline + the new F-SMOKE2 saved-schema contract test; the 9 failing
  suites are all non-auth:
  `fixed-header`, `new-header`, `site-header`, `preloader/test-colors`,
  `preloader/test-preloader`, `seo/article-ad-experience`,
  `seo-catalog`, `ad-orchestration/LayoutSystem`,
  `ad-orchestration/PlacementEngine`. All auth suites green:
  `auth-p0`, `auth-p1`, `consistency`, `laravel-client`, `OtpScreen`,
  `OtpInput`.
- Known stderr noise (all pre-existing, non-fatal): `[csrf] No CSRF_SECRET set`,
  `unstable_cacheLife`/`unstable_cacheTag` deprecation errors (the
  former `output: standalone` boot-blocker is fixed — F-E2E6), stale
  `baseline-browser-mapping` notice.

### Post-verdict API surface smoke (non-auth, 2026-10-09 — all fixed)

A dedicated smoke of the BFF routes S1–S13 does not cover (`e2e/smoke.ps1`
→ `api-smoke.mjs` + `api-smoke2.mjs`) found 4 non-auth defects, fully
diagnosed with wire-level evidence in `e2e/api-smoke-report.md` — all four
are now **fixed and re-verified green** (campaign **22/22**, probes
**13/13**, 2026-10-09):

- F-SMOKE1 (`PATCH /api/settings`) — BFF now forwards **flat** keys and
  its closed key set mirrors `CustomerSetting::KEYS` (minimal edit to the
  WIP `application/settings/settings.ts`, go-aheaded); wrapper/unknown
  keys → 422 at the BFF, valid patches → 200.
- F-SMOKE2 (`POST /api/saved`) — BFF schema/route now carry
  `{ref_type: puja|content|service, ref_id: ULID}` mirroring
  `SavedItemController::store` (`title` dropped — no backend column);
  `auth-p1.test.ts` fixtures moved to the new contract (+1 test).
- F-SMOKE3 (`sessions/revoke-others`, backend M5-domain) — the actor-session
  sweep now spares the **presented** session (the verified `actor` request
  attribute `logout` already reads): the caller survives its own call on
  the bearer path (was 401, repro 2/2 → fixed 2/2). New backend
  regression test
  `IdentitySelfServiceTest::test_bearer_path_revoke_others_spares_the_presented_session`.
  The `current:true` bearer-path caveat was reclassified as by-design
  (web-session inventory; the presented credential is an actor session).
- F-SMOKE4 (`ads/decision`) — route zod now mirrors every non-optional
  `DecisionRequest` field; malformed input → **422** with field details
  (was 500); engine untouched.

The post-fix re-run also surfaced **F-SMOKE5** (tracked in the smoke
report): the campaign's fixed verification email collided with the
previous run's globally-unique `users.email` → `confirm()` 500 (probe
fixed by per-run email randomization; backend half — duplicate email
should be a graceful 409, not an unhandled unique violation —
**closed 2026-10-09 on explicit go-ahead**: `confirm()` catches
`UniqueConstraintViolationException` → 409 `conflict`, pinned by
`CustomerVerificationTest::test_confirm_conflicts_when_contact_belongs_to_another_account`).

Gates re-run after the fixes: build/typecheck/Pint ✅, Jest 340/20
(baseline + 1 new schema test), sqlite 647/647, MySQL 647·0·14 (same
documented errors), API smoke 22/22 + 13/13. None of this touches the
auth surface; the E2E row above reflects the post-fix re-run.

---

## 6. How to reproduce

```powershell
# full pipeline (DB reset, migrate/fresh + tenant seed, servers, warm-up,
# HTTP scenarios, BFF restart + cache:clear, Playwright, teardown)
powershell -ExecutionPolicy Bypass -File e2e\run.ps1
```

- OTP codes are read from the drill log (`OTP_LOG_CODES=true`, off by
  default; production behavior unchanged).
- Server logs captured to `e2e/.logs/` (`laravel-out/err`, `bff1/bff2-out/err`).
- Pacing: backend `throttle:10,1` is one shared fixed window per IP;
  `thHit()` burns 8/window and waits out decay (3 × 61 s sleeps per run).
- PHPUnit gate: drop/recreate `navdhya_test_central` + `navdhya_test_tenant`
  first (exact SQL in the `phpunit.mysql.xml` header; since the F-DB2 fix
  the suite also self-heals a stale `tenant_shared` on its first database
  test), then run it from `platform-navdhya`:
  `.\vendor\bin\phpunit.bat -c phpunit.mysql.xml --colors=never` (~2 min;
  expected baseline: 649 tests with the 14 known non-auth errors F-DB3a–c,
  0 failures).
- Safety rails: only `navdhya_`-prefixed DBs are touched; scoped MySQL user
  holds privileges on the four `navdhya_*` databases only; `.env` and sqlite
  files are never written (env-var overrides ride real process environment).

## 7. Tracked items (need go-ahead)

1. ~~Backend half of the hard gate~~ **DONE 2026-10-09**: C1/C2/C4
   (+ G1–G4) implemented as M2–M5 in `platform-navdhya`; §9b re-run green
   (E2E 83/83, PHPUnit both drivers); `p0-contract.md` tags flipped,
   `backend-security-gaps.md` + `laravel-contract.md` truth-upped.
2. Deferred decisions: login-CSRF, `code`-alias removal (one-release),
   closing `LEGACY_HEADER_AUTH`.
3. Redis adapter for rate limiters (in-process is single-instance today;
   idempotency store likewise).
4. The 9 pre-existing non-auth Jest suites.
5. ~~Candidate BFF hardening~~ **DONE 2026-10-09 (explicit go-ahead)**:
   `callLaravel` maps `TimeoutError` → `DEPENDENCY_UNAVAILABLE` 504
   envelope carrying the BFF's own requestId (non-timeout errors keep
   the propagate-to-500 convention); pinned by 2 new Jest cases — see
   `e2e/api-perf-report.md` §7.
6. Commit strategy for the working tree: one thesis vs split code/docs.
7. MySQL portability tickets from the first MySQL PHPUnit baseline
   (verdict F-DB3a–c): source-driver in `tenant:provision-schema`,
   `timestamp` → `dateTime` on far-future expiry columns, ULID vs UUID
   in `TemplePujaFactory`. App/factory code read-only this cycle; a
   test-side shim would mask the app bugs instead of fixing them.

## 8. Working tree

Uncommitted on `architecture/phase-3-web-extraction` @ `ae6a960`:

- **This mission (code)**: `apps/web/adapters/backend/*`,
  `apps/web/app/api/verifications/route.ts`,
  `apps/web/app/api/mfa/verify/route.ts` (rotation adoption),
  `apps/web/application/state-schemas.ts`,
  `apps/web/components/auth/OtpScreen.tsx`,
  `apps/web/components/dlo-x/shells/DashboardShell.tsx` + their tests;
  **F-SMOKE fixes (post-verdict smoke)**: `apps/web/app/api/settings/route.ts`,
  `apps/web/app/api/saved/route.ts`, `apps/web/app/api/ads/decision/route.ts`,
  `apps/web/application/__tests__/auth-p1.test.ts` (contract fixtures),
  minimal F-SMOKE1 edit in `apps/web/application/settings/settings.ts`
  (go-aheaded — see §5), backend
  `IdentitySelfServiceController` (presented-session exclusion) + new
  `IdentitySelfServiceTest::test_bearer_path_revoke_others_spares_the_presented_session`;
  **backend M2–M5**: `platform-navdhya/database/migrations/2026_10_09_*`
  (actor_sessions, mfa lockout columns, `superseded_at`),
  `app/Platform/Identity/*` (`MfaService`, `MfaLockedException`,
  `ActorTokenIssuer`, `ActorTokenVerifier`, `ActorSessionRotation`,
  `Models/ActorSession`, `MfaController`, `UserAdminController`,
  `IdentitySelfServiceController`), `app/Modules/Customers/Auth/CustomerOtpService.php`,
  `app/Modules/Customers/Models/CustomerOtpChallenge.php`,
  `app/Modules/Customers/Http/Controllers/CustomerAuthController.php`,
  `app/Modules/Customers/Http/Controllers/CustomerVerificationController.php`
  (F-SMOKE5 confirm-write 409 guard) + new
  `tests/Modules/Customers/CustomerVerificationTest::test_confirm_conflicts_when_contact_belongs_to_another_account`,
  `app/Platform/Console/CleanupExpiredCommand.php`, `app/Models/User.php`,
  new tests (`CustomerOtpSupersessionTest`, `MfaLockoutTest`,
  `ActorSessionRevocationTest`) + adapted (`ActorTokenVerifierTest`,
  `HmacTokenMiddlewareTest`, `SuspensionEnforcementTest`);
  `platform-navdhya/tests/TenantIntegrationTestCase.php` (F-DB2
  MySQL-gate recovery override — the only `RefreshDatabase` consumer);
  **perf cycle**: new `platform-navdhya/app/Platform/Http/Middleware/
  RequestTiming.php` + `bootstrap/app.php` prepend (PERF_TIMING-gated
  log-only timing), cost-10 raw-attribute signup hash in
  `CustomerAuthController.php` (user-approved — `e2e/api-perf-report.md`
  §5/§5.1), `laravel-client.ts` timeout→504 mapping (+2 Jest cases).
- **This mission (docs)**: `auth/*` (contract truth-up, gaps, this report),
  `platform-navdhya/phpunit.mysql.xml`, `e2e/*`.
- **User WIP (do not touch)**: `CHANGELOG.md`, `commit_msg.txt`,
  `apps/web/app/astrology/*`, `apps/web/app/community/stories/*`,
  `apps/web/application/settings/settings.ts` (except the sanctioned
  minimal F-SMOKE1 key-set edit), `.turbo/cache` deletions,
  `platform-navdhya/` (read-only apart from `phpunit.mysql.xml`, the
  F-DB2 override in `tests/TenantIntegrationTestCase.php`, the
  M2–M5 backend code/tests/migrations authorized for this phase, and
  the perf instrumentation (`RequestTiming` + `bootstrap/app.php`
  prepend) and approved `CustomerAuthController` cost-10 edit),
  `.env` and sqlite files.
