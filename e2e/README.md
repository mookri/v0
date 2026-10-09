# Full-stack E2E (frontend → BFF → Laravel → MySQL)

Production-grade test gates for the auth surface, driving the **real**
stack — no mocks, no sqlite, no simulated backend guarantees.

```
browser / node fetch  →  Next.js BFF (:3000)  →  Laravel (:8000)  →  MySQL 8
```

## Prerequisites

- MySQL 8 running on `localhost:3306` (root/`123456789` for provisioning,
  scoped user `navdhya_e2e`/`navdhya_e2e_2026` for all DML)
- PHP 8.x + Composer deps installed in `platform-navdhya/`
- Node 20+ / pnpm; `apps/web` built — `run.ps1` builds **only when
  `.next` is missing**, so rebuild manually after changing `apps/web`
  code (`Remove-Item -Recurse -Force apps\web\.next; $env:NEXT_STANDALONE='0'; pnpm --filter @navdhya/web run build`)
  or the run will exercise a stale bundle
- Ports **8000** and **3000** free (the orchestrator takes them over)

## Run

```powershell
powershell -ExecutionPolicy Bypass -File e2e\run.ps1              # HTTP scenarios + Playwright UI flow
powershell -ExecutionPolicy Bypass -File e2e\run.ps1 -HttpOnly    # skip the browser phase
```

Exit code 0 iff every assertion in every phase passed.

## What runs

| Phase | File | Coverage |
|---|---|---|
| HTTP S1–S13 | `scenarios.mjs` (zero-dep node) | wire + MySQL + contract columns, verdict matrix printed |
| UI flow | `ui.spec.ts` (Playwright, chromium) | signup → logout → login → OTP 3-attempt exhaustion → restart → login → logout |

### Scenario → contract map (S1–S13)

| S | Scenario | Contract |
|---|---|---|
| S1 | signup challenge issued, `expiresIn=600`, DB row `attempts=0` | §1/§4, V2 |
| S2 | Idempotency-Key replay → same challenge; different body → 409 | §4 |
| S3 | unknown challengeId → uniform 401, no existence oracle | V4 |
| S4 | wrong OTP ×3 (attempts 1→2→3, row survives) + 4th call deletes | §3, **C4/M3 enforced** |
| S5 | successful redeem → 201 + cookie, challenge row consumed, user created | §2, **M3 enforced** |
| S6 | `{channel,value}` accepted (M1): fresh value → 202 + row; own verified contact → documented 409; legacy `{type,contact}` → 422 | **M1 fix** |
| S7 | cookie-first: cookie works, absent/garbage cookie → hard 401 | §7 |
| S8 | login redeem with canonical `proof` → cookie, `receivedForm=proof` | §2 |
| S9 | logout clears cookie; **bearer dead directly against Laravel (401)** + `actor_sessions.revoked_at` + audit row | **G4 / M5 enforced** |
| S10 | resend supersedes: **active=1** (rows retained), superseded redeem → 401 | **C2/G1 / M2 enforced** |
| S11 | MFA enroll (AAL1 only), confirm, step-up row, **rotation adopted (G3)**, M4 lockout drill **G2** (429 + Retry-After), BFF 5/900 throttle, recovery codes single-use / cannot revoke | §5, V6–V8, **G2/G3 enforced** |
| S12 | signup IP throttle → 429 + Retry-After | §8 |
| S13 | §9b wire concurrency: C2 overlapping resends → active=1; C1 overlapping redeems → one winner + uniform 401; C4 overlapping wrong codes → attempts exactly 3 | **C1/C2/C4 enforced** |

**Enforced-reality scenarios** pass when the backend's actual (now
hardened) behavior is observed — they pin real backend state
(`auth/backend-security-gaps.md`, M2–M5 landed 2026-10-09), never a
hoped-for one. The security-improvement gate (§9b: C1/C2/C4/G1–G4) is
green; these rows remain the regression tripwire against regressions.

## Rate-limit pacing (why the run takes minutes)

The backend's `throttle:10,1` on the public auth + MFA routes is **one
shared fixed window per IP** (`sha1(domain|ip)`, not per-route —
probe-verified: 10 mixed-route requests pass, the 11th gets 429). The
full matrix spends 25 backend-reaching requests, so `scenarios.mjs`
burns **8 per window** (`thHit()` before exactly the requests that hit
a throttled route) and waits out the ~61s decay rather than
manufacturing mid-matrix 429s. The backend is the security authority;
the E2E paces to its real throttle instead of fighting it.

`run.ps1` additionally issues **one direct warm-up signup** (26th
throttled request) right after both servers are ready: PHP's cold
route-compile on the first auth POST takes ~3s — over the BFF's
`fast` (2s) class — which timed S1 into an uncaught empty 500 and
cascaded into S2/S4. Going direct to Laravel bypasses the BFF's
timeout classes, and the extra slot fits the pacing budget (8 paced +
1 warm = 9 of 10; the backend window also opens earlier than
`thHit()`'s first sleep, so it decays first).

Before the Playwright phase, `run.ps1` restarts the BFF (fresh
in-process limiters) **and** runs `php artisan cache:clear`
(`CACHE_STORE=database`: the shared throttle window would otherwise
survive the restart and starve the UI phase's 8 hits; sessions live in
the `sessions` table, untouched).

## UI logout

`ui.spec.ts` logs out through the real UI affordance: the header user
dropdown in `DashboardShell` → **Logout** → `POST /api/auth/logout`
(cookie cleared per contract §6, redirect to `/login`). The button was
found inert on the first UI run (no handler) and is now wired to the
existing BFF route.

## How OTP codes are obtained (drill mode)

`run.ps1` boots Laravel with `OTP_LOG_CODES=true`
(`config/customers.php::log_otp_codes`, **off by default**), which logs
`challenge_id` + `code` to `storage/logs/laravel.log*`. Both phases read
the newest entry after issuing a challenge. Production behavior is
unchanged when the flag is absent.

## Safety rails

- Only `navdhya_e2e_central` / `navdhya_e2e_tenant` are created/dropped
  (`navdhya_test_*` belong to the PHPUnit MySQL gate). Everything else is
  refused by an explicit guard in `run.ps1`.
- `platform-navdhya/.env` and both sqlite files are never written: all
  DB overrides ride **real process environment variables**, which win
  over `.env` (immutable Dotenv repository).
- The scoped MySQL user holds privileges only on the four `navdhya_`
  databases.
- Servers are torn down in `finally`; databases are left in place for
  post-mortem queries (re-running the script re-provisions them).

## API surface smoke (non-auth routes)

`smoke.ps1` boots both servers against the **existing** `navdhya_e2e_*`
databases (no reprovision, no Playwright, no build) and probes the routes
S1-S13 does not cover: settings, saved, notifications, ads
decision/track/track-beacon, revalidate, sessions revoke-others,
verifications confirm, mfa recovery-codes.

```powershell
powershell -ExecutionPolicy Bypass -File e2e\smoke.ps1                            # api-smoke.mjs (22 assertions)
powershell -ExecutionPolicy Bypass -File e2e\smoke.ps1 -Script api-smoke2.mjs     # targeted probes (13)
```

Prerequisites: `.next` built, `navdhya_e2e_*` provisioned (run `run.ps1`
once), ports 8000/3000 free. Identity phones are randomized per run, so
the smoke is re-runnable against an existing database; drill codes and
throttle pacing work exactly like `run.ps1`.

Campaign findings (full detail + post-fix evidence:
`e2e/api-smoke-report.md`): F-SMOKE1..4 - settings/saved BFF wire
mismatch (unconditional backend 422), bearer-path `revoke-others`
logging the caller out with the others, and `ads/decision` 500s on
opportunities missing `placementHints` - are **fixed and re-verified
green** (campaign 22/22, probes 13/13). F-SMOKE5 (backend half of the
verification-confirm duplicate-email 500; probe half fixed by email
randomization) stays open in that report.

## Related gates

- `platform-navdhya/phpunit.mysql.xml` — PHPUnit against MySQL
  (reset `navdhya_test_*` first; see header of that file)
- `pnpm --filter @navdhya/web run test` — Jest (BFF contract unit gates)
