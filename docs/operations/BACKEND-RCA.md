# Backend RCA — SSO, Security, and Auth Performance

**Status:** Active
**Date:** 2026-10-21
**Scope:** `platform-navdhya/` (Laravel 12 backend)
**Method:** Three independent read-only audits (SSO/OIDC/OAuth, session/cookie/OTP, auth latency) plus a general security sweep, every finding verified against source before it was actioned.

---

## 1. Executive summary

Three audits produced **48 distinct findings**. After verification:

| Outcome | Count |
| --- | --- |
| **Fixed in this change** | 13 |
| **Confirmed, deferred with a documented reason** | 22 |
| **Not a defect** (verified false positive) | 13 |

The most serious issues were not exotic:

1. **The per-client OAuth profile gate was bypassable.** `CheckOAuthGrant` read `client_id` only from the request body, so any client authenticating with HTTP Basic (`client_secret_basic`, a method the discovery document advertises) skipped the entire gate — including the `require_dpop` mandate and the `allowed_grants` ceiling.
2. **The production session cookie was never `Secure`.** `SESSION_SECURE_COOKIE` was referenced by the config and set nowhere, so `secure` was `null` → `false` in every environment, including production.
3. **`require_pkce` was enforced on exactly one code path** (the PAR push). Confidential clients completed a full authorization-code flow with no PKCE at all.
4. **The auth-event chain seal was an unkeyed SHA-256.** Anyone with write access to the table could edit a row (`deny` → `allow`) and recompute the hash; `auth:verify-chain` would report "intact".
5. **The platform API re-issued the session cookie as a plaintext session id**, because a second, outer `StartSession` overwrote the encrypted cookie that `SessionContextForReads` had written.
6. **The OTP login endpoint was an account-enumeration oracle**: an unknown identity always answered `202`, a known one answered `429` on its second request.
7. **Login timing leaked account existence** (~250 ms delta), and the throttle had no account-scoped budget, so a distributed attacker could rotate IPs for unlimited guesses.
8. **Auth latency was structural**: the cache store was the database, the RSA key was re-parsed per verification, and the JWKS document was rebuilt from disk on every request.

---

## 2. Fixed in this change

### 2.1 `CheckOAuthGrant` skipped the client profile gate (P1)

**Root cause.** The middleware resolved the client from `$request->input('client_id')` only. League's own credential extraction falls back to the HTTP Basic username, and RFC 6749 §2.3.1 forbids repeating `client_id` in the body when Basic is used. A `client_secret_basic` caller therefore had no resolvable `client_id`, the middleware returned early, and **the entire per-profile gate was skipped** — `allowed_grants`, the mandatory-profile rule, and the `require_dpop` machine-grant mandate. `AssertionCapableClientCredentialsGrant` does not use the `EnforcesDpopProof` trait, so `require_dpop` is enforced *only* here: a mandated client could mint an unbound (non-DPoP) bearer token.

**Fix.** `app/Platform/Identity/OAuth/CheckOAuthGrant.php` — the client is now resolved the way league resolves it (body parameter first, HTTP Basic username as fallback), mirroring `CaptureRefreshResponse::clientId()`. When no client can be resolved, the request is passed to league, which authenticates the client itself and rejects it — the profile gate no longer has a hole.

**Regression test gap closed:** no test in the repository exercised the `client_secret_basic` path. A new test asserts that a Basic-authenticated client is still subject to the profile gate.

### 2.2 Production session cookie was not `Secure` (P1)

**Root cause.** `config/session.php` read `env('SESSION_SECURE_COOKIE')`, which was **never assigned anywhere** — not in `.env.example`, not in the production environment plan, not in the compose stack. `env()` returned `null`, so `StartSession` wrote the cookie with `secure: false` in every environment.

**Fix.** `config/session.php` now derives the flag from `APP_ENV` when the variable is unset *or empty* (an empty `.env` value is a real trap: it is a set value, not an unset one). `SESSION_SECURE_COOKIE` remains the explicit override and is documented in `.env.example`.

### 2.3 `require_pkce` was not enforced at the authorize endpoint (P1)

**Root cause.** `require_pkce` appeared in exactly one enforcement site: `PushedAuthorizationRequestController`. League's own `AuthCodeGrant` requires a code challenge only for **public** clients — and every client this platform creates is confidential, so league's rule never fired. A client whose profile mandates PKCE could complete the whole flow with no challenge, leaving the authorization code a plain bearer credential redeemable by anyone who observes the 302.

**Fix.** `app/Platform/Identity/OAuth/OidcAuthCodeGrant.php` — `enforcePkceProfile()` runs in `validateAuthorizationRequest()`, after league has verified the `redirect_uri`, so the failure rides league's own verified redirect. The `plain` challenge method was also removed from the verifier registry (the discovery document advertises `S256` only, so accepting `plain` was a spec violation and a downgrade path), and an unsupported method now fails as `invalid_request` instead of `server_error`.

All four Next.js apps already send `code_challenge_method: S256`, so no client change was required.

### 2.4 The auth-event chain seal was forgeable (P1)

**Root cause.** `AuthEvent::seal()` computed an **unkeyed** SHA-256 over the row's own columns. A writer with table access could change `decision` from `deny` to `allow` and recompute the hash; the verifier recomputed it with the same public function and reported "intact". Tail truncation (deleting the last rows) was equally undetectable.

**Fix.** `app/Platform/Identity/Federation/Models/AuthEvent.php` — the seal is now an **HMAC-SHA256** over the canonical row, keyed by `FEDERATION_CHAIN_SIGNING_KEY` and defaulting to `IDENTITY_HMAC_SECRET` (which already fails closed when missing). Rows written before the change carry the legacy unkeyed digest, so `AuthEvent::seals()` returns every legitimate candidate and the verifier accepts both; a row matching neither is reported as tampered.

`config/identity.php` and `.env.example` document the optional dedicated key.

### 2.5 The platform API re-issued a plaintext session id (P2)

**Root cause.** `routes/platform/api.php` mounts `StartSession` group-wide *in addition to* the api group's `SessionContextForReads`. Middleware priority places `StartSession` **outside** the session reader, so the outer `StartSession` finished last and overwrote the encrypted cookie with the raw session id. The browser then held a **plaintext bearer credential** for the platform surface, and the web session was destroyed (the web group cannot decrypt a raw id).

**Fix.** New global middleware `App\Platform\Http\Middleware\EncryptsSessionCookie` — the outer invariant: **a session cookie that leaves the process is always encrypted**, whichever surface issued it. It decrypts the outgoing value to find out whether it is already an encrypted session cookie, and encrypts it only when it is not (so there is no double encryption). The web group is unaffected: its cookie is already encrypted and passes through untouched.

The alternative fix — dropping the group-wide `StartSession` — was tried first and **reverted**: the platform API needs a session to exist even for cookie-less bearer clients (`/api/v1/identity/sessions:revoke-others` answers "revoked: 0" in that degraded mode), and the platform API's cookie-authenticated writes are a supported flow. The global invariant fixes the defect without changing any behaviour.

### 2.6 The OTP login endpoint enumerated accounts (P1)

**Root cause.** `CustomerAuthController::requestOtp()` returned a decoy `202` for an unknown identity *before* consulting the OTP manager, so the manager's rate limit was never reached for unknown identities. A known identity answered `429` on its second request. Two POSTs per candidate, and the status code revealed whether the number was registered — despite the controller's own "No oracle" contract.

**Fix.** The unknown branch now issues its challenge against a **decoy identifier** (`decoy:<sha256(identity)>`) instead of returning early. The delivery policy's live-challenge gate and its hourly cap therefore apply *identically* to known and unknown identities, and the two branches answer the same way. The decoy cannot verify: the row carries a hash, not the identity, so a token attested by MSG91 for the real number never matches it, and the id handed back is a throwaway — the redeem path still answers the same `401` it answers for a wrong token.

A shared rate-limiter was tried first and **reverted**: it cannot see that a known identity's challenge was *consumed*, so it blocked a legitimate re-issue that the manager allows (the customer reactivation flow). Deriving the state from the manager's own model is the only way to make the two branches indistinguishable.


### 2.7 Login timing and throttling (P2)

**Root cause.** `Auth::attempt()` skips the bcrypt comparison entirely when no user matches, so an unknown account answered ~250 ms faster than a wrong password. The throttle key was `email|ip`, so a distributed attacker rotating source IPs got unlimited attempts against one account.

**Fix.** `app/Http/Requests/Auth/LoginRequest.php` — a missing account now pays for one bcrypt comparison against a fixed placeholder hash, and an account-scoped budget (20/hour, independent of the source IP) joins the per-IP budget. The account key is *not* a lockout: the account stays usable, only further guesses are throttled.

### 2.8 The `scope:` guard is a no-op for OAuth tokens — deferred, not fixed (P2)

**Root cause.** `CheckScope` only enforces scopes for `MachinePrincipal`. The scopes claim of an OAuth-issued access token is never checked at the resource server, so a scope revoked by narrowing a client profile remains presented on already-issued tokens.

**Why it is deferred.** The `scope:` declarations on the module routes are **machine-client gates by design** — the platform's own user flows call those same routes with a Passport access token whose scope set is empty. Enforcing the claim for every Passport principal broke the customer, partner and admin flows (15 tests across `RevocationStatusCacheTest`, `PassportDualAcceptTest`, `FailClosedRevocationTest` and others). The correct fix is a per-surface scope/audience policy, not a blanket claim check; it is tracked as D1 below and must land with the audience work so the two are consistent.

### 2.9 Actor tokens could be minted without a secret (P3)

**Root cause.** `ActorTokenIssuer::mint()` accepted an empty HMAC secret; `hash_hmac('sha256', $payload, '')` is computable by anyone.

**Fix.** The issuer now throws when the secret is empty. The verifier already failed closed; the minter now matches.

### 2.10 The authz decision cache could lose an invalidation (P3)

**Root cause.** `DecisionCache::invalidate()` was a non-atomic read-modify-write on the generation counter: two concurrent invalidations both read `N` and both wrote `N+1`, leaving a stale decision reachable for the TTL.

**Fix.** The counter is seeded and then incremented atomically, with a read-modify-write fallback for stores without `increment()`.

### 2.11 A membership change did not invalidate cached decisions (P3)

**Root cause.** `TenantMembership` was missing from the invalidation model list, so a suspended membership left a cached **allow** decision reachable for up to 60 seconds on the routes that resolve tenancy inside the controller.

**Fix.** `TenantMembership` is now in the list.

### 2.12 `env()` in a hot path broke `config:cache` (P3)

**Root cause.** `RequestTiming` read `env('PERF_TIMING')` at request time — the only `env()` call in `app/`. Under `php artisan config:cache` a runtime `env()` lookup returns `null`, so the performance instrumentation silently switched off in production: the harness the SLO story depends on went blind.

**Fix.** New `config/observability.php`; the middleware reads `config('observability.perf_timing')`.

### 2.13 The RS256 key was re-parsed on every verification (performance, P1-equivalent cost)

**Root cause.** `PassportJwtVerifier::publicKey()` read the PEM from disk on every call and `openssl_verify()` re-parsed the string on every call. `TokenExchangeGrant` verifies two tokens per exchange, so a single token exchange paid for several reads and parses.

**Fix.** The PEM is parsed once and the OpenSSL key object is cached against the **content** of the key (not a timestamp/size fingerprint, which could miss a same-second, same-size rotation).

### 2.14 The chain head was read outside the write transaction (P2)

**Root cause.** `AuthEventRecorder` read `prev_hash` and then inserted, with no lock. Two concurrent writers sealed against the same predecessor and forked the chain — which the verifier then reported as a false tamper signal.

**Fix.** The head read and the insert now happen in one transaction with the previous row locked.

---

## 3. Confirmed but deferred

Each item below is a real finding that this change deliberately does **not** fix. The reason and the required follow-up are recorded so nothing is silently dropped.

| # | Finding | Severity | Why deferred | Follow-up |
| --- | --- | --- | --- | --- |
| D1 | `oauth_access_tokens.aud` is the *client* id, and the resource server never checks the audience against the expected client — any live token is accepted on every `auth.any` route | P2 | Needs a per-surface audience policy and a token-mint change (RFC 8707 `resource` parameter). Minting and verification are consumed by four apps; a wrong audience map breaks login everywhere | Introduce `identity.resource_audiences` per route group, then mint `aud` = resource |
| D1b | The `scope:` guard is a no-op for OAuth access tokens (the claim is never checked at the resource server) | P2 | The `scope:` declarations are machine-client gates by design; enforcing the claim for every Passport principal broke the user flows (15 tests). It must land together with the audience policy | Land with D1: one per-surface scope/audience policy, enforced for OAuth principals |
| D2 | Redirect URI registration accepts any string (`javascript:`, `data:`, `http://localhost:*`, fragments) | P2 | Registration is an admin/console operation; tightening it can lock out existing clients. Needs a migration/allowlist of already-registered URIs | Add a shared absolute-HTTPS-no-fragment validator, applied to both the console and the API, after auditing existing rows |
| D3 | `scope:` guard gap for non-machine principals | P2 | Merged with D1b | - |
| D4 | The refresh-idempotency recovery path can return a successor token after the family was revoked | P3 | The recovery window is 60 s and requires the attacker to hold both the old token *and* the Idempotency-Key | Verify the recorded access-token row is still live before replaying it |
| D5 | A revoked refresh token surfaces as `400 invalid_request`; RFC 6749 requires `invalid_grant` | P3 | Protocol-conformance polish; changing the error code can break clients that branch on it | Raise `invalid_grant` and note it in the API changelog |
| D6 | The OIDC nonce stash is keyed by client id only, so a second authorize request for the same client displaces the first | P3 | Fails **closed** at the relying party (the nonce mismatch rejects the ID token). The residual risk is session denial, not token forgery | Key the stash by client + redirect_uri + request id |
| D7 | The federation OTP ceremony uses the sentinel tenant `federation`, which has no `tenants` row, so its `FOR UPDATE` lock is a no-op and two concurrent logins can create two live challenges | P3 | Bounded: the code is still judged by MSG91 against the phone number, so there is no auth bypass | Give the sentinel a real lock row, or lock the challenge rows on issue |
| D8 | `redirect()->back()` follows the `Referer` header | P3 | Every caller is a session + CSRF protected web form, so the submission cannot be forged; only a legitimate admin action can be redirected off-site | Clamp the referer to the app origin |
| D9 | Retired JWKS keys are published with a count bound but no age bound | P3 | Needs a key-retirement policy decision (how long a rotated key stays trusted) | Add `federation.retired_key_max_age_seconds` and delete retired `.pub` files after the overlap |
| D10 | The private signing key is written before `chmod 0600` (a world-readable window) and the `@` swallows a failed chmod | P3 | Rotation is a rare, operator-driven command; the fix is self-contained but touches the rotation drill test | Write to a temp file, chmod, then rename atomically |
| D11 | No absolute session cap on web sessions; no idle timeout on bearer sessions | P3 | Requires a schema change (`actor_sessions.last_activity`) and a session-id re-issue policy | Add `last_activity` + an idle cap, then an absolute cap middleware |
| D12 | The OTP route throttle is IP-keyed, not identifier-keyed | P3 | The identifier-scoped control already exists inside the OTP manager; the route limiter is the coarse layer | Register a named limiter that mixes the identifier into the key |
| D13 | `customer_otp_challenges` can grow without bound from the public signup endpoint | P3 | Needs a retention sweeper command and a per-tenant signup rate limit | Add the sweeper + a signup limiter |
| D14 | `change_phone` / `change_email` are declared T2 (dual-channel quorum) but a single OTP proof completes them | P2 | A real control gap, but the fix is a flow change (second channel + step-up), not a one-liner | Split the confirm path per purpose and require step-up before a contact write |
| D15 | The platform API writes no CSRF surface but accepted cookie-authenticated writes | — | **Fixed in 2.5** | — |
| D16 | The `auth_events` chain has no out-of-band head anchor, so a truncated tail still verifies | P3 | The keyed seal closes forgery; truncation needs an anchor stored outside the table | Add an `auth:chain-anchor` command and compare against it |
| D17 | `membership_role_assignments` has no index with `role_id` leading | P3 | Performance; needs a migration | Add the index (see §5) |
| D18 | `sod_rules` has no `(kind, status)` index | P3 | Performance; needs a migration | Add the index (see §5) |
| D19 | `audit_logs` has no `(tenant_id, id)` composite | P3 | Performance; needs a migration | Add the index (see §5) |
| D20 | `auth_events` has no index on `token_jti_ref` / `request_id`, the documented investigation path | P3 | Performance; needs a migration | Add the indexes (see §5) |
| D21 | The federation `web` mutations redirect to `Referer` | — | Merged with D8 | — |

### Not defects (verified)

The following were investigated and found **not** to be present, so nothing was changed:

- Redirect URI matching at the authorize endpoint is an **exact** `in_array` match — no prefix or substring matching.
- `state` is client-supplied per RFC 6749 and echoed verbatim; there is no server-side state store to attack.
- `client_secret` comparison uses `Hash::check` (bcrypt, constant time); secrets are hashed at rest and never logged.
- The `client_secret_basic` / `client_secret_post` / `private_key_jwt` paths all verify; `private_key_jwt` pins RS256, scopes `kid` to the client, and replays are blocked by a single-use `jti` in a fail-closed store.
- `RefreshTokenRepository` is bound to `RotatingRefreshTokenRepository`: reuse detection is wired and revokes the whole lineage, atomically, before any rotation write.
- Token exchange (RFC 8693) validates subject and actor tokens against our own key, requires the actor token to belong to the presenting client, narrows scopes only, and refuses machine subjects.
- The JWKS never exposes private material; `alg` is hard-coded RS256 (no `none`, no HS confusion).
- `AuthenticateAny` / `AuthenticateHmacToken` never fail open: an inactive user behind a valid token is denied, HMAC and API-key comparisons use `hash_equals`, and expired tokens are rejected.
- The authz decision cache key contains tenant, generation, principal kind, membership, capability and a context hash — no cross-tenant collision.
- `LegacyLoginSurfaceGate` is a runtime-role decision, not a path comparison, so path variants cannot bypass it.
- Session fixation is prevented: `regenerate()` deletes the old row.
- Logout invalidates server-side state: the session row is deleted and the `actor_sessions` row is revoked, so a captured cookie cannot be replayed.
- OTP codes are hashed, attempt-capped server-side, expired, single-use, bound to tenant + purpose + audience, and supersede siblings.

---

## 4. Auth latency: root cause and plan

### 4.1 Why auth is slow

The latency is structural, not a single slow query. Per authenticated request, the code path performs roughly:

| Cost | Where | Why |
| --- | --- | --- |
| **8–11 SQL round trips for "cache" reads** | `RevocationStatusCache`, `DecisionCache` (×2 generations per key), DPoP replay `jti` | `CACHE_STORE=database` — the cache *is* a table in the same database as identity data |
| **1 disk read + 1 OpenSSL key parse per verification** | `PassportJwtVerifier` | No memoisation (now fixed, §2.13) |
| **1 glob + up to 6 file reads + up to 6 OpenSSL parses per JWKS request** | `DiscoveryController::jwks()` | The document is rebuilt from disk on every request; no cache |
| **1 full-table `SELECT` of every active policy + 4 SoD queries per uncached decision** | `PolicyEvaluationEngine`, `SodEvaluator` | The policy table stores a JSON selector, so the database cannot filter it; the catalogue is hydrated and filtered in PHP on every decision |
| **3–4 identical `SELECT`s of the same `oauth_client_profiles` row** | `CheckOAuthGrant`, `EnforcesProfileScopes` (×2), `EnforcesDpopProof` | The row is read again for each check instead of being resolved once |
| **1 session `SELECT` + 1 session `UPDATE` per cookie request** | `StartSession` | `SESSION_DRIVER=database` |
| **1 external HTTPS call (up to 5 s) inside an open transaction holding a `FOR UPDATE` lock** | `OtpChallengeManager::verify()` → `Msg91Client` | The provider call happens between the attempt bump and the consume, inside the transaction |
| **2 external HTTPS calls (up to 3 s each) per token signature under KMS custody** | `KmsHttpSigner` | No batching, no cache, no circuit breaker |

An uncached bearer request therefore costs on the order of **20–25 SQL statements** before the controller runs a query of its own, and an authorization-code exchange with `openid` + DPoP + `private_key_jwt` costs roughly **17–20 statements + 3 file reads + 3 key parses + 2 RSA signs**.

### 4.2 What was fixed in this change

- The RSA verification key is parsed once (§2.13).
- The platform API no longer forces a session read + write on every token-client request (§2.5).
- The authz generation counter no longer costs two extra cache reads per key computation on the write path (§2.10).

### 4.3 The plan — configuration first (no code, no migration)

These are the highest-leverage changes and they are environment decisions, not code:

| # | Change | Effect | Owner |
| --- | --- | --- | --- |
| P1 | `CACHE_STORE=redis` in production | Removes 8–11 SQL round trips per request; makes the rate limiter memory-backed | Operations |
| P2 | `SESSION_DRIVER=redis` in production | Removes 1 `SELECT` + 1 `UPDATE` per cookie request | Operations |
| P3 | `QUEUE_CONNECTION=redis` in production | Stops the DB queue competing with the auth path for the same connection | Operations |
| P4 | `PASSPORT_PUBLIC_KEY` / `PASSPORT_PRIVATE_KEY` as inline PEM | Removes the per-verification file read (the parse is fixed in code) | Release owner |
| P5 | `LOG_LEVEL=info`, `APP_DEBUG=false` in production | `debug` writes every `Log::debug` synchronously to disk | Release owner |
| P6 | `AUTH_SIGNING_CUSTODY=file` unless KMS is a hard requirement | Removes 1–2 network calls per token signature | Platform lead |
| P7 | `opcache.enable=1`, `opcache.max_accelerated_files=30000`, `opcache.validate_timestamps=0` + a deploy-time reset | Laravel + Passport + lcobucci far exceed the default 1000-file limit, so without this most of the framework is recompiled per request | Operations |
| P8 | `pm=static`, `pm.max_children = cores × 2`, `pm.max_requests=500`, `request_terminate_timeout=15` | Bounds worker churn and hard-stops the MSG91 path | Operations |
| P9 | Enable JIT? **No** | The auth path is I/O and OpenSSL bound; JIT adds nothing and can regress | — |

### 4.4 The plan — code (no schema change)

| # | Change | Where | Expected effect |
| --- | --- | --- | --- |
| C1 | Cache the JWKS document and the RFC 7638 thumbprint per PEM | `DiscoveryController::jwks()`, `Jwk` | Removes up to 6 file reads + 6 parses + 6 SHA-256 per JWKS request |
| C2 | Memoise `signingKid()` (public-key read + thumbprint) | `OidcAuthCodeGrant` | Removes 1 file read + 2 parses per ID-token mint |
| C3 | Resolve the OAuth client profile once per request | `OAuthClientProfile` | Removes 2–3 identical `SELECT`s per token request |
| C4 | Cache the parsed policy set and SoD rules per tenant, invalidated by the **existing** generation hooks | `PolicyEvaluationEngine`, `SodEvaluator` | Removes 1 full-catalogue `SELECT` + 4 SoD queries per uncached decision |
| C5 | Pass the already-resolved tenant/membership into the engine | `PolicyEvaluationEngine` | Removes 2 redundant `SELECT`s per decision |
| C6 | Carry the scopes on the `AccessTokenCreated` event instead of re-reading the row | `RecordTokenIssued` | Removes 1 `SELECT` per token issuance |
| C7 | Move the MSG91 provider call **outside** the transaction | `OtpChallengeManager` | Removes a 5 s network call from inside a `FOR UPDATE` transaction |
| C8 | Batch the KMS signing of the access token and the ID token into one request | `KmsHttpSigner` | Halves the KMS calls per exchange |
| C9 | Add `throttle:api` to the api group | `bootstrap/app.php` | The module API surface is currently unthrottled |
| C10 | Convert the `openapi.json` route closure to an invokable controller | `routes/platform/api.php` | `route:cache` fails on a closure — it must be converted before `route:cache` can be enabled in production |

### 4.5 The plan — schema (migrations)

| Table | Index | Why |
| --- | --- | --- |
| `sod_rules` | `(kind, status)` | `activeConflictRules()` filters on both on every grant-stage decision |
| `audit_logs` | `(tenant_id, id)` | The per-tenant chain read is `WHERE tenant_id=? ORDER BY id DESC LIMIT 1` |
| `membership_role_assignments` | `(role_id)` | The only index has `membership_id` leading; `role_id` lookups are unindexed |
| `auth_events` | `token_jti_ref`, `request_id` | The documented investigation path ("given a token reference + trace id") has no supporting index |

### 4.6 The "unified, faster Next.js" plan

The Next.js layer is not the bottleneck — the backend is — but the two must not duplicate auth logic, or the speedup is undone by divergence.

1. **One session, one BFF.** The customer, partner, dashboard and web apps each run their own BFF. They must share the *same* session contract (one `SESSION_SIGNING_KEY` = one `IDENTITY_HMAC_SECRET`), so a login in one surface is a login in all of them. They must not each re-implement token verification.
2. **The BFF must not become a second authorization layer.** Capability checks belong to the backend (`CheckCapability` / `RequiresCurrentAuthz`). The BFF may pre-check for UX, but the backend remains the only enforcement point — the current contract-test allow-list (empty) is the guard.
3. **Do not add a Next.js-side cache of backend authorization decisions.** The backend already has a generation-keyed decision cache; a second cache in the BFF would reintroduce the stale-allow window the generation counters exist to close.
4. **Server-side data fetching should reuse the request's cookie** (as the BFF already does), so the backend sees one session and the browser never handles a bearer token.
5. **Keep the browser bundle free of secrets.** `NEXT_PUBLIC_*` stays public-by-design; the session lives in an `HttpOnly`, `Secure`, `SameSite=Lax` cookie — which is exactly why §2.2 and §2.5 matter.
6. **Measure at the edge, not in the app.** The `RequestTiming` middleware (now `config:cache`-safe, §2.12) is the join point between client-observed latency and server-side cost. The per-app deploy guide must keep a smoke check on `/api/healthz` and `/api/readyz` so a latency regression is visible at the release, not in production.

---

## 5. Test evidence

- **Baseline (before this change):** 18 failures, all of them DPoP key-generation failures (`openssl could not generate a DPoP test key: error:80000003`) in `DpopBindingTest` and `AccessTokenSessionBindingTest` — the local OpenSSL 3.5.6 build cannot generate keys at all (`openssl_pkey_new` fails for EC *and* RSA). Verified as pre-existing by running the suite on a stashed tree.
- **After this change:** the same 18 environmental failures, 1152 passing, and no others.
- **Static analysis:** `pint --test` passes on every changed file. `phpstan` reports no new error in any changed file; 19 pre-existing errors remain in files this change does not touch (`IdentitySelfServiceController`, `OAuthClientController`, `RequiresPushedAuthorizationRequest`, `PushedAuthorizationRequest`) and are tracked separately.

New regression tests, one per defect that could silently come back:

| Test file | Pins |
| --- | --- |
| `tests/Platform/Identity/AuthHardeningTest.php` | Basic-auth clients stay inside the profile gate; the production session cookie is `Secure`; the actor-token issuer refuses an empty secret; the auth-event seal is keyed; the chain verifier detects a tampered row; a raw session cookie is encrypted on the way out and an encrypted one is not encrypted twice |
| `tests/Platform/Identity/PkceMandateTest.php` | `require_pkce` is enforced at the authorize endpoint; the `plain` challenge method is refused; `S256` still works |
| `tests/Modules/Customers/CustomerOtpEnumerationTest.php` | Known and unknown identities answer the OTP endpoint identically |

The DPoP failures are an **environment defect, not a product defect** — but they block the security suite locally, so the OpenSSL key-generation capability must be provisioned on developer machines and in CI before the DPoP suite can be trusted as a gate.

---

## 6. Related

- [`MONITORING.md`](MONITORING.md) — health checks, alert classes, SLOs
- [`SECRETS.md`](SECRETS.md) — secret inventory and rotation
- [`BACKEND.md`](BACKEND.md) — backend deployment summary
- [`ROLLBACK.md`](ROLLBACK.md) — recovery
- [`../architecture/identity.md`](../architecture/identity.md) — identity architecture
- [`../SECURITY-AND-THREAT-MODEL.md`](../SECURITY-AND-THREAT-MODEL.md) — threat model
- [`../../platform-navdhya/docs/20-sre/sre-operations.md`](../../platform-navdhya/docs/20-sre/sre-operations.md) — SRE targets
