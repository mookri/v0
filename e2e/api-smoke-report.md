# API surface smoke - report (2026-10-09, post-fix re-run green)

## Scope and method

`e2e/scenarios.mjs` (S1-S13, 83/83) proves the **auth / session / MFA /
verification** surface. This smoke covers the **other** BFF routes that
S1-S13 never exercises, against the real stack (Next.js BFF -> Laravel ->
MySQL, `navdhya_e2e_*` databases), using the same env contract as
`run.ps1` plus `REVALIDATION_SECRET` for the revalidate probe.

Scripts (added to the harness):

- `e2e/api-smoke.mjs` - 22-assertion campaign (all uncovered routes)
- `e2e/api-smoke2.mjs` - 13 targeted probes (regression set for the four
  fixes below + the direct-backend parity checks)
- `e2e/smoke.ps1` - orchestrator (boot -> probe -> teardown; no
  reprovision, no build, no Playwright)

```powershell
powershell -ExecutionPolicy Bypass -File e2e\smoke.ps1                        # 22 assertions
powershell -ExecutionPolicy Bypass -File e2e\smoke.ps1 -Script api-smoke2.mjs # targeted probes
```

Campaign results: **original** run 1 = 17/22, run 2 = 9/10 (diagnosed the
4 findings below); **post-fix re-run 2026-10-09** = run 1 **22/22**, run 2
**13/13** - all green. Backend throttle pacing (10/min shared fixed window)
honored via the `thHit()` pattern from `scenarios.mjs`; each `smoke.ps1`
invocation `cache:clear`s on boot, resetting the window.

## Verdict summary (post-fix)

| Route | Verdict | Evidence |
|---|---|---|
| POST /api/auth/signup | OK | 202 + challengeId (re-run: twice) |
| POST /api/auth/verify | OK | 201 + session cookie (re-run: twice) |
| GET /api/settings | OK | 200 {sections, values} (values = backend keys) |
| PATCH /api/settings | **FIXED (F-SMOKE1)** | re-run: 200 flat `{locale, notifications_email}`; wrapper + unknown key rejected 422 at the BFF (R2d/R2e) |
| GET /api/saved | OK | 200 items[] |
| POST /api/saved | **FIXED (F-SMOKE2)** | re-run: 201 `{ref_type:'puja', ref_id:<ULID>}`; list reflects the add |
| POST /api/notifications/read-all | OK | 200 |
| GET /api/sessions | OK (see caveat note below) | 200; bearer path presents an actor session, not a `sessions` row - see "Not findings" |
| POST /api/sessions/revoke-others | **FIXED (F-SMOKE3)** | re-run: `revoked:4` -> next call **200** (was 401); R4b `revoked:1` -> R4c **200**; new bearer-path PHPUnit regression test green |
| POST /api/verifications | OK | 202 + drill challenge |
| POST /api/verifications/confirm | OK (F-SMOKE5 fully fixed) | re-run: 200 verified_at set (email randomized per run); backend 409 guard added 2026-10-09 |
| POST /api/mfa/enroll | OK | 201 otpauth_url |
| POST /api/mfa/confirm | OK | 200 + 10 recovery codes (TOTP) |
| POST /api/mfa/recovery-codes | OK | 200 + rotation (codes differ) |
| POST /api/ads/decision | **FIXED (F-SMOKE4)** | re-run: valid payload 200 `decisions[]` + 403 CSRF gate; bare payload **422** with zod field details (was 500) |
| POST /api/ads/track | OK | 200 accepted:1 |
| POST /api/ads/track-beacon | OK | 200 duplicates:1 (idempotency dedup) |
| POST /api/revalidate | OK | 403 CSRF / 401 wrong secret / 200 registered slug revalidated:1 |

## Findings

### F-SMOKE1 - PATCH /api/settings: BFF wire was unconditionally rejected - **FIXED**

**Root cause (original run):**
- BFF forwarded wrapped, backend expects flat:
  `apps/web/app/api/settings/route.ts` sent `{ settings: validation.values }`;
  the backend (`CustomerSettingController::update`) diffs the **top-level**
  request keys against `CustomerSetting::KEYS`, so it saw the key
  `settings` -> 422 `Unknown settings: settings.`
- Key sets diverged: BFF `KNOWN_SETTINGS_KEYS`
  (`application/settings/settings.ts`) used `email_notifications`,
  `sms_notifications`, `timezone`, `currency`, ... while the backend
  accepts only `locale | notifications_email | notifications_sms |
  marketing_opt_in`. Only `locale` overlapped - and even that failed
  because of the wrapper.
- Evidence: R2a flat `{locale:'en'}` -> 422; R2b `{notifications_email:true}`
  -> 422 `Invalid settings.`; R2c direct backend flat -> **200** with the
  resolved 4-key settings.

**Fix (BFF aligns to backend authority):**
- `application/settings/settings.ts`: `KNOWN_SETTINGS_KEYS` shrunk to the
  backend's 4 keys; the per-key type switch now mirrors the backend rules
  (`locale`: non-empty string <= 8 chars; the three notification/marketing
  keys: booleans). *(Minimal edit to user WIP, explicitly go-aheaded.)*
- `app/api/settings/route.ts`: forwards `validation.values` **flat** (no
  wrapper); docstring corrected (unknown keys are **rejected 422** at the
  BFF, not silently dropped - that wording was always wrong).

**Re-run evidence (22/22 + 13/13):**
- campaign1: `settings PATCH (flat backend keys)` -> **200**
- R2a flat `{locale}` -> **200**; R2b `{notifications_email}` -> **200**
  (now `backend:true` - it reaches Laravel); R2c direct -> **200**
- R2d wrapper `{settings:{...}}` -> **422 at the BFF** (closed key set
  rejects the unknown key `settings` before any network hop)
- R2e dropped key `{timezone}` -> **422** (closed set = backend set)
- GET round-trip: `values` carries exactly the backend's keys.

### F-SMOKE2 - POST /api/saved: BFF omitted backend-required fields - **FIXED**

**Root cause (original run):**
- BFF forwarded `{ref_id, title}` only (`app/api/saved/route.ts`), but the
  backend (`SavedItemController::store`) requires `ref_type`
  (`in:puja,content,service`) and a **ULID-shaped** `ref_id`
  (`^[0-9A-HJKMNP-TV-Z]{26}$`). Missing `ref_type` -> 422 on every call.
- The BFF `savedAddSchema` had no `ref_type` and admitted any
  `[A-Za-z0-9_-]{1,64}` ref (non-ULIDs the backend would also reject).
- Evidence: run 1 saved POST -> 422; probe R3 direct backend
  `{ref_type:'puja', ref_id:<26-char ULID>}` -> **201**.

**Fix:**
- `application/state-schemas.ts`: `savedAddSchema` = `{ ref_type:
  z.enum(['puja','content','service']), ref_id: trim + ULID regex }`,
  mirroring `SavedItemController::store`; `title` **removed** (the store
  has no title column - forwarding one promised a write that can never
  round-trip).
- `app/api/saved/route.ts`: forwards exactly `{ref_type, ref_id}`;
  docstring updated to state the no-title rationale.
- `application/__tests__/auth-p1.test.ts`: fixtures moved to the new
  contract (valid `{ref_type, ULID}`, missing-`ref_type` rejected,
  unknown-`ref_type` rejected, non-ULID rejected; +1 test).

**Re-run evidence:**
- campaign1: `saved POST (ref_type + ULID)` -> **201** with the created
  row; `saved GET reflects add` items=1 (re-run uses a random ULID so an
  existing database never 409s).
- probe R3 (direct backend parity) -> **201**, unchanged.

### F-SMOKE3 - revoke-others removed the presented session on the bearer path - **FIXED**

**Root cause (original run):**
- Contract: "end every *other* session" (BFF route docstring
  `sessions/revoke-others/route.ts:8`); backend tests asserted survival
  only on the **cookie** path.
- `IdentitySelfServiceController::revokeOtherSessionsJson` excluded
  `session()->getId()`. The platform API group runs `StartSession`
  group-wide (`routes/platform/api.php`), but the BFF calls with a
  **bearer token and no session cookie**, so Laravel mints a fresh ad-hoc
  session id that matches **no row** - the web-row exclusion was a no-op
  there, and the unconditional `ActorSession::revokeForUser()` sweep also
  revoked the caller's **own** jti (the verified actor AuthenticateAny
  stamps on the request) -> the next bearer call 401'd.
- Evidence (repro 2/2): run 1 `revoked:4` -> next GET **401**; run 2
  (clean user) `revoked:1` -> next GET **401**.

**Fix (backend, M5-domain):**
- `IdentitySelfServiceController`: the actor-session sweep now spares the
  **presented** session - read from the same `actor` request attribute
  logout reads (`session_id`). Cookie path presents no actor session
  (session user wins the auth precedence), so every actor session there
  belongs to another device and the full sweep stands unchanged. The
  web-row sweep is untouched (excludes a real current session id when one
  exists; on the bearer path all web rows genuinely are "others").
- New regression test
  `IdentitySelfServiceTest::test_bearer_path_revoke_others_spares_the_presented_session`:
  mints two actor sessions, presents one as a bearer, asserts
  `revoked:1` (other device's web row swept), the presented jti still
  verifies, the other jti is revoked.

**Re-run evidence (22/22 + 13/13):**
- campaign1: `sessions/revoke-others` -> 200 `revoked:4`, then
  `current session survives revoke-others` -> **200** (was 401).
- R4b `revoked:1` -> R4c presented-session survival -> **200** (was 401).
- Targeted PHPUnit: `IdentitySelfServiceTest` 6/6, `ActorSessionRevocationTest`
  4/4 (M5 rotation/logout semantics unchanged).

**`current` flag caveat - reclassified as not-a-bug:** on the bearer path
the presented credential is an **actor session**, not a `sessions` row;
the index is web-session inventory only by design (controller docblock:
"Token-platform sessions land with the token program"). No listed row can
be the caller's presentation, so `current:false` for every row (R4a
`current_true=0`) is factually correct; the cookie path (platform web)
marks `current:true` and is unit-tested. No code change made here.

### F-SMOKE4 - POST /api/ads/decision: 500 on partial opportunities - **FIXED**

**Root cause (original run):**
- The route zod only required `slotId` per opportunity
  (`object({slotId}).passthrough()`), while downstream
  `CampaignRepository.ts:18` / `LayoutResolver.ts:101` call
  `opportunity.placementHints.some(...)` unguarded (same for
  `request.user.*`, `request.page.*`, `client.*`, `session.*` in the
  ranking stack). Missing `placementHints` -> `Cannot read properties of
  undefined (reading 'some')` -> **500**.
- Evidence: run 1 bare `{slotId:'s1'}` -> 500; probe R1 canonical
  Jest-fixture payload -> **200** with `decisions[]`; CSRF gate 403.

**Fix (validate at the edge - the route's own documented intent):**
- `app/api/ads/decision/route.ts`: the envelope zod now mirrors every
  non-optional `DecisionRequest` field the engine dereferences -
  opportunity: `surface` (enum), `placementHints` (non-empty string
  array), `anchorId`, `anchorIndex`, `reservedSize {width,height}`,
  `depthBucket`, `lazy`; top level: `page`/`user`/`client` present
  objects, `session {id, engagementScore, scrollDepth}`. Schema failure
  -> **422** (§8 wrong-shape status; was a generic 400 for schema and a
  500 for engine crashes); malformed JSON stays 400; CSRF stays 403.
  Unknown fields pass through; the engine itself is untouched.

**Re-run evidence (22/22 + 13/13):**
- R1 valid payload -> **200** `decisions[]`; campaign1 valid payload +
  CSRF -> **200**, missing CSRF -> **403**.
- R1b bare `{slotId}` -> **422** with zod details
  (`path: ["opportunities",0,"surface"]`) - was 500.

### F-SMOKE5 - verifications/confirm 500 on duplicate email (found on post-fix re-run)

- **Probe half - FIXED:** the campaign's verification probe used a fixed
  `smoke@example.com`. `CustomerVerificationController::confirm`
  (line 188) writes `users.email`, which is **globally unique** - the
  second campaign run collided with the first run's user:
  `SQLSTATE[23000] Duplicate entry 'smoke@example.com' for key
  'users.users_email_unique'` -> unhandled -> 500. The probe now derives
  a per-run email from the randomized phone (`smoke-<phone suffix>@example.com`),
  restoring the re-runnable requirement; confirm -> 200 on re-run.
- **Backend half - FIXED (2026-10-09, explicit go-ahead):** a
  duplicate-email confirm is now a graceful **409 `conflict`** —
  `CustomerVerificationController::confirm` catches
  `UniqueConstraintViolationException` around the `users.email` /
  `users.phone` write and returns the same conflict envelope class as
  signup's duplicate-phone 409 (challenge consumed; caller re-requests).
  Pinned by
  `CustomerVerificationTest::test_confirm_conflicts_when_contact_belongs_to_another_account`
  (409 + `error.code=conflict` + nothing written); campaign/probe
  re-run green (22/22 + 13/13).

## Verified working (live, post-fix re-runs)

- Auth re-verified outside the E2E harness: signup 202 / verify 201 +
  cookie, twice, two identities (fresh randomized per run).
- settings GET + PATCH round-trip, saved GET + POST round-trip,
  notifications/read-all, sessions GET.
- Full verification flow: start (email) -> drill code -> confirm 200.
- Full MFA rotation flow: enroll 201 -> TOTP confirm 200 (10 recovery
  codes) -> recovery-codes regenerate 200 (new set differs).
- ads decision (valid payload), track, track-beacon (incl. cross-route
  idempotency dedup of the same key).
- revalidate: CSRF 403 gate, secret 401 gate, happy path 200 with the
  registry slug actually revalidated.
- revoke-others on both runs: other sessions swept, caller survives.

## Not findings (by design)

- Backend error wording is not passed through - the BFF owns error
  messages (`laravelErrorResponse` fallbacks). Observed and expected.
- `track-beacon` `accepted:0 duplicates:1` for a repeated event is the
  idempotency dedup working.
- Cold-boot timing on first auth POST - same F-E2E5 environmental class;
  the direct-backend warm-up from `run.ps1` was reused.
- `sessions` index `current:false` on the bearer path - see F-SMOKE3
  caveat note (web-session inventory; the presented credential is an
  actor session by design).

## Impact on the auth regression gates

Re-verified after the fixes - none broken:

- Build (`next build`) ✅, typecheck ✅, Pint ✅ (2 touched PHP files).
- Jest full: **340 passed / 20 failed** - exact known baseline + 1 new
  schema test; the 20 are the same pre-existing non-auth suites.
  Targeted affected suites (auth-p1, ads routes, csrf): **19/19**.
- PHPUnit sqlite: **647 tests / 3703 assertions - 0 failures** (646 + the
  new bearer-path regression test).
- PHPUnit-on-MySQL: fresh `navdhya_test_*` re-run - see `regression-verdict.md`
  §1 for the recorded number.
- E2E S1-S13 paths untouched by the fixes (revoke-others, settings, saved
  and decision are not exercised by S1-S13; sessions GET and logout code
  paths unchanged) - full `run.ps1` re-run recorded in the verdict.
- F-SMOKE1..5 are all non-auth functional findings, now **fixed**
  (F-SMOKE5's backend half closed 2026-10-09 with the confirm-write
  409 guard), separate from the deferred §7 auth items.
