# Unified auth curl scoreboard (ADR-0020 live drill)

**Status:** P8e evidence — every leg below was executed live against the
e2e stack (MySQL `navdhya_e2e_*`, Laravel `artisan serve`, customer app
`next dev`) with `AUTH_MODE=federated`, issuer `http://auth.localhost:8000`.
Observed results are real responses, truncated for length; tokens, OTP
codes, and client secrets are never reproduced.

Re-run this scoreboard after any change to the federation surface
(`app/Platform/Identity/OAuth`, federation routes, or either BFF's
`server/federation.ts`).

## 1. Drill topology

| Piece | Value |
| --- | --- |
| Issuer (`AUTH_ISSUER`) | `http://auth.localhost:8000` (Laravel serves on `127.0.0.1:8000`, reached with `Host: auth.localhost:8000`) |
| Runtime | `monolith` (single process serves issuer + business API; host gate separates them) |
| Customer BFF | `http://localhost:3001`, `CUSTOMER_AUTH_MODE=federated` |
| OAuth clients | created via `php artisan platform:client:create`: one `first_party_web` per BFF (authorization-code + PKCE), one `first_party_service` for introspection (client-credentials). Since A-SEC-004-6 every console-created client also needs `--scope=openid` (empty allowed_scopes fails closed) plus `--backchannel-logout-uri` (`/api/auth/backchannel-logout` on the app's own origin) — re-register with both flags when recreating clients |
| Drill identity | phone `9898989898` / dev shortcut code `123456` (Msg91 shortcut token attests `919898989898`) |

### curl conventions that matter

- Always send `-H "Host: auth.localhost:8000"` **with the port** — a
  port-less Host breaks URL generation (redirects/consent form actions
  come back wrong).
- Always send `-H "Accept: application/json"` on API legs — without it,
  `$request->validate()` failures render a 302 HTML redirect instead of
  the 422 JSON envelope.
- The federation login session is a Laravel database session
  (`laravel_session` cookie + CSRF meta `csrf-token`, echoed as
  `X-CSRF-TOKEN` on state-changing calls).
- PowerShell 5.1 mangles inline JSON passed to `curl -d` — write bodies
  to files and use `-d @file`.

## 2. Scoreboard

| # | Leg | Command (shape) | Observed |
| --- | --- | --- | --- |
| 1 | Runtime probe (auth host) | `GET /auth/health` with auth Host | `200` — `mode=federated`, `runtime=monolith`, `issuer=http://auth.localhost:8000` |
| 2 | Host gate (business host) | `GET /auth/health` on `127.0.0.1:8000` (no auth Host) | `404` — federation surface invisible off the auth host |
| 3 | Legacy login sunset | `POST /auth/login` (legacy ceremony) on auth host | `404` envelope — `RuntimeRole::servesLegacyLoginSurface()` is false for every runtime when mode=federated (P8c) |
| 4 | Discovery metadata | `GET /.well-known/oauth-authorization-server` | `200` — `issuer`, `authorization_endpoint`, `token_endpoint`, `revocation_endpoint`, `jwks_uri` all on `http://auth.localhost:8000` (issuer-based after the P8e fix; previously mis-advertised `APP_URL`) |
| 5 | JWKS | `GET /.well-known/jwks.json` | `200` — RS256 key with RFC 7638 thumbprint `kid` |
| 6 | Federation page | `GET /auth/login` (HTML) | `200` — unified login (phone first, email & password alternate), CSRF meta present |
| 7 | OTP issue | `POST /auth/login/otp` `{phone:"919898989898"}` | `202` — `challenge_id`, `expires_in: 1200`; real SMS sent (rate windows: throttle 10/min per route, fresh-challenge gate per identity) |
| 8 | OTP verify (dev shortcut) | `POST /auth/login/verify` `{challenge_id, token:"test-token-…"}` | `200` — `federation_session authenticated:true`, actor `fed_…` (auto-provisioned user, D1: no tenant membership created) |
| 9 | Session whoami | `GET /auth/session` | `200` — authenticated user id + name |
| 10 | Legacy BFF login (federated) | `POST :3001/api/auth/login` | `404` `NOT_FOUND` envelope — legacy ceremony routes are dead in federated mode |
| 11 | BFF authorize | `GET :3001/api/auth/authorize?redirect=/bookings` | `302` to issuer `/oauth/authorize` with PKCE `S256` + `state`; flow state in `nv-auth-flow` HttpOnly SameSite=Lax cookie (10 min) |
| 12 | Consent | `GET /oauth/authorize?…` with session | `200` — consent page naming the client ("Authorize Customer BFF"), hidden `_token` / `state` / `client_id` / `auth_token` |
| 13 | Approve | `POST /oauth/authorize` (form, CSRF + auth_token) | `302` to `{redirect_uri}?code=…&state=…` — state round-trips intact |
| 14 | Code single-use | replay the same code at `/oauth/token` | `400` `invalid_grant` — "Authorization code has been revoked" |
| 15 | Token exchange | `POST /oauth/token` `grant_type=authorization_code` + `code_verifier` (client_secret_post) | `200` — access (RS256) + refresh. Claims: `aud` = presenting client (string), `jti`, `iat`/`nbf`/`exp` (+900 s), `sub` = user id, `scopes: []`, **no `iss`** (league never signs one — the signature is the issuer binding) |
| 16 | Userinfo | `GET /oauth/userinfo` with Bearer | `200` — `sub` (external id), `phone_number`, `phone_number_verified:true` (identity endpoint returns verified claims; the business profile masks, see leg 19) |
| 17 | Introspection (active) | `POST /api/oauth/introspect` `token=…&client_id=…&client_secret=…` (service client, **body** credentials) | `200` — `active:true` + `client_id`/`sub`/`exp`/`iat`. Restricted to `first_party_service` clients (carve-out C4); introspection client auth is client_secret_post |
| 18 | Refresh rotation | `POST /oauth/token` `grant_type=refresh_token` | `200` — new access (new `jti`) + new refresh; the **predecessor access token row is revoked** at rotation (verified in `oauth_access_tokens.revoked`) |
| 19 | Business data over Bearer | `GET /api/v1/customers/profile` with Bearer + `X-Tenant-Id` | `200` — `buyer_profile` with `phone:"919******98"` (server-side masking) |
| 20 | Reuse detection | present the already-rotated refresh | `400` `invalid_grant` — lineage killed: introspecting the rotated access token answers `active:false` |
| 21 | Revocation | `POST /api/oauth/revoke` `token=…&token_type_hint=access_token&client_id=…&client_secret=…` (**body** credentials) | `200`; follow-up introspection → `active:false`. (Basic-only auth is refused with `400` before any revocation — both BFF logouts were fixed to client_secret_post during this drill) |
| 22 | SSO auto-approve | second `/oauth/authorize` while a live token lineage exists | `302` straight to `{redirect_uri}?code=…` — no consent screen. When the lineage is fully dead, consent is required again (observed both) |
| 23 | BFF session decode | curl `:3001/api/notifications/unread-count` with `nv_customer_token=<access>` | `200` — cookie decoded (`sub`/`jti`/`aud` direction check), upstream Bearer call verified by Laravel |
| 24 | BFF profile proxy | curl `:3001/api/profile` with the session cookie | `200` — proxied buyer profile (masked phone) |
| 25 | Browser leg | `localhost:3001/login` → auth.localhost unified login → dev shortcut → consent → callback → session → protected page | full chain completes in a real browser; client-side fetches (notifications poll) answer `200` through BFF + Laravel (re-executed live 2026-10-16 on the localhost-rebound stack — transcript in §3) |

## 3. Browser-leg transcript (live 2026-10-16)

Executed in a real browser against a localhost-rebound stack: the
harness browser routes only plain `localhost`, so the serve ran with
process-env `AUTH_DOMAIN=localhost` / `AUTH_ISSUER=http://localhost:8000`
(drill env otherwise unchanged; checked-in `.env` untouched). The §1/§2
`auth.localhost` shape was curl-driven before the rebind — the host gate
follows the env either way (`auth.localhost` Host 404s once rebound).

1. `GET http://localhost:3001/login` → BFF `/api/auth/authorize` → `302` issuer `/oauth/authorize?client_id=…&code_challenge=S256…&state=…&nonce=…`.
2. No issuer session → `302 /auth/login` (unified page: phone primary, email & password alternate).
3. Phone `919898989898` + Send code → first attempt `429` (fresh-challenge gate — a leg-era held row for the identity); `DELETE` the identifier's `customer_otp_challenges` rows, retry → `202` challenge, OTP panel + resend countdown.
4. Code `123456` → the page's dev shortcut redeems `test-token-9898989898` → `POST /auth/login/verify` → `200`; `session.login allow/credentials_verified` chained for `sub:8cd71414…`. Detour, recorded: user3 still carried leg 43's `mfa_enabled=1` with **no `mfa_secret` enrolled**, so the first verify returned `202 mfa_required` and no TOTP can ever pass (the typed `000000` tries chain as `session.login deny/mfa_failed`, attempt counter 2/5); the test-only flag was reset to `mfa_enabled=0` and a fresh challenge completed OTP-only — the ordinary non-MFA shape.
5. `302` back to the original authorize URL (BFF `state` + PKCE survive the login hop) → consent screen ("Authorize Customer BFF" — lineage was cold) → Authorize.
6. `302 {localhost}:3001/api/auth/callback?code=…&state=…&iss=http://localhost:8000` — RFC 9207 `iss` rides the browser redirect too.
7. BFF callback: timing-safe `state` check → back-channel `/oauth/token` (`code_verifier`, client_secret_post) → `nv_customer_token` session cookie (HttpOnly, SameSite=Lax) → `302` home. The first exchange died in a PHP `max_execution_time` fatal mid-`mintIdToken` (host I/O stall during the session's restart storms — the access token row had already persisted and audited `token.issued`); re-entry via `:3001/login` rode the fresh SSO session through auto-approve to a second exchange → landed authenticated on `http://localhost:3001/`.
8. Session-bearing pages: Home + primary nav render through the BFF session; `/profile` shows its "Failed to load profile" state — expected (leg 47 left this identity's buyer profile `deactivated` and membership `suspended`; fail-closed holding, auth-orthogonal to the session itself).

## 4. Findings fixed during the drill

1. **No `iss` claim in league tokens.** The BFF session decode direction-checks `aud` (the client the token was minted for) instead; lcobucci serializes a single audience as a string, the platform verifier tolerates string-or-array, and so does the BFF. Upstream verifies the signature on every proxied call — decode is routing context only.
2. **Discovery metadata advertised `APP_URL` endpoints.** Now prefers `federation.issuer` when configured (`DiscoveryController`), pinned by `DiscoveryTest`.
3. **Revocation requires client credentials in the body.** Both BFF logouts sent Basic auth, which the endpoint refuses before revoking (silent no-op behind best-effort). Fixed to client_secret_post; the jest contract test pins the body shape.
4. **Dev-shortcut canonical mismatch.** The shortcut token attests `919898989898`; a bare 10-digit challenge canonicalizes to 10 digits and can never match. The login page now requests the challenge in the `91…` form for shortcut phones (server-side canonical equality stays strict — possession proof must match the requested identifier).
5. **Customer middleware pinned the prod cookie name.** The edge gate checked only `__Host-nv_customer_token`, but `__Host-` requires Secure and dev HTTP sets the bare name → infinite login↔callback loop. The gate now accepts both minted names; the request handlers remain the real judges.
6. **Stale module caveat (operational).** A `next dev` process keeps serving pre-edit server modules after some server-file edits — restarting the dev server is required to trust a live result. Symptom: curl-probe logic passes standalone while the running server still rejects.

## 5. Known gaps carried forward

- **Deactivate-by-OTP is dead in federated mode** — account-status changes need a federation-side step-up; tracked as follow-up.
- **BFF and Laravel flips land together** — `CUSTOMER_AUTH_MODE=federated` and the issuer's `AUTH_MODE=federated` must be deployed in one change set; each side fail-closes on its own misconfiguration but the pair has no independent rollback.
- **RSC relative fetch (pre-existing, auth-orthogonal).** The customer app's protected pages call `customerApi` from server components; `fetch('/api/profile')` is relative and Node's fetch rejects it before any network I/O, so those pages render their error/empty state. This fails identically in direct mode and is unrelated to federation — the BFF routes and client-side fetches answer `200` with a federated session (legs 23–25). Fix belongs to the app's data layer (absolute base for server-side requests).
- **Federation auto-provision creates no tenant rows (D1 by design)** — a verified login provisions only the central `User`; tenant membership and the buyer profile row are materialized separately (drill inserted both manually). Product decision pending on which surface owns that backfill.

## 6. Drill artifacts (regenerate, don't reuse)

- Clients: `platform:client:create --name=… --type=first_party_web|first_party_service` (secrets shown once).
- PKCE: `base64url(random_bytes(32))` verifier, `base64url(sha256(verifier))` challenge.
- Identity: phone `9898989898`, dev shortcut code `123456` — real SMS is sent on every issue; the OTP windows (throttle 10/min, fresh-challenge gate, hourly cap) apply per identity.

## 7. Phase 9a legs (ADR-0021) — live ✅ (executed 2026-10-16)

The legs below were added in phase 9a (PAR, RFC 9207 `iss`, OIDC ID
token, per-profile scope ceiling). Every behavior they exercise is
already pinned by the automated suite (`PushedAuthorizationRequestTest`
14/14, `ParEnforcementTest` 9/9, `AuthorizationResponseIssuerTest` 4/4,
`OidcIdTokenTest` 10/10, `DiscoveryMetadataTest` 4/4 — full Laravel suite
998/998). All eleven legs were then executed live on 2026-10-16 against
the relaunched e2e stack with the §2 conventions (auth Host header,
`Accept: application/json`, `-d @file` bodies); each returned its
expected status and body (Observed column). Setup applied first:
`require_par=true` on an N2 client, `allowed_scopes=['openid']` on the
BFF web clients (N1, PAR opt-in off); clients regenerated per §6.

| # | Leg | Command (shape) | Expected | Observed (live 2026-10-16) |
| --- | --- | --- | --- | --- |
| 26 | PAR push | `POST /api/oauth/par` `{client_id, client_secret, response_type:"code", redirect_uri, scope:"openid", state, code_challenge, code_challenge_method:"S256", nonce}` | `201` — `{request_uri, expires_in:120}`; `request_uri` stored only as a hash | ✅ `201` + `request_uri` |
| 27 | PAR push — bad scope | same body with `scope:"read"` (profile allows only `openid`) | `400` league `{error:"invalid_scope", …}` — no redirect, no `request_uri` row | ✅ `400 invalid_scope` |
| 28 | PAR push — missing nonce | same body, drop `nonce` | `400` `{error:"invalid_request"}` — openid requires a nonce at the push | ✅ `400 invalid_request` |
| 29 | N2 mandate | `GET /oauth/authorize` on an N2 (`require_par`) client **without** `request_uri` | `302` `{redirect_uri}?error=invalid_request…` (+ `state`, then `iss`) — refused, not served | ✅ `302` `error=invalid_request` (+`state`, +`iss`) |
| 30 | N2 resume with request_uri | `GET /oauth/authorize?client_id=…&request_uri=…` (no other params) | `200` — consent page (pushed params replay from the row) | ✅ `200` consent page |
| 31 | `iss` on approval | approve a N1 authorize → `POST /oauth/authorize` | `302` `{redirect_uri}?code=…&state=…&iss=http://auth.localhost:8000` — byte-equal to discovery | ✅ `302` `code` + `state` + `iss` byte-equal to discovery |
| 32 | `iss` on denial | `DELETE /oauth/authorize?auth_token=…` | `302` `{redirect_uri}?error=access_denied&state=…&iss=…` | ✅ `302` `access_denied` + `iss` |
| 33 | `iss` on `prompt=none` | `GET /oauth/authorize?…&prompt=none` as a guest | `302` `{redirect_uri}?error=login_required&state=…&iss=…` (never a login bounce) | ✅ `302` `login_required` + `iss`, no login bounce |
| 34 | `openid-configuration` | `GET /.well-known/openid-configuration` | `200` — same RFC 8414 doc + `id_token_signing_alg_values_supported:["RS256"]`, `scopes_supported:["openid"]`, `nonce_supported:true`, `subject_types_supported:["public"]`; **no** `require_pushed_request_parameters` | ✅ `200` — OIDC doc as listed |
| 35 | OIDC exchange → id_token | token-exchange the code from an `openid` authorize | `200` — access + refresh + **`id_token`** (RS256, `kid` ∈ JWKS, `iss` byte-equal, `aud` = client, `nonce` round-trip, `exp−iat=600`, ceremony `auth_time`/`amr`/`acr`/`sid`) | ✅ `200` + `id_token`, claims round-trip verified |
| 36 | id_token absent on non-openid | token-exchange a `scope=` code; refresh; `client_credentials` | `200` — access (+refresh) but **no `id_token`** on any of the three | ✅ `200` — no `id_token` on all three |

Section §7 is the live-curl checklist for the 9a enforcement points; the
automated suite is the authoritative green/red for each behavior. The
browser leg (§3) also completed live on 2026-10-16: the full federation
ceremony ran in a real browser through OIDC callback, token exchange, and
an authenticated customer-app session.

## 8. Phase 9b legs (ADR-0022) — machine-grant scope ceiling, live ✅ (executed 2026-10-16)

Added in phase 9b: the A5 per-profile `allowed_scopes` ceiling on the
three machine grants. Pinned by `MachineGrantScopeCeilingTest` 9/9 (full
suite 1007/1007). Setup applied for this run: one machine/service client
with `allowed_scopes=[]` (the fail-closed default) and another with
`allowed_scopes=['openid']`; a scoped (`openid`) subject token minted
via an OIDC authorize for legs 39–40. All four legs ran live on
2026-10-16 and matched their expected outcomes.

| # | Leg | Command (shape) | Expected | Observed (live 2026-10-16) |
| --- | --- | --- | --- | --- |
| 37 | cc scope outside ceiling | `POST /oauth/token` `{grant_type:"client_credentials", client_id, client_secret, scope:"openid"}` on a client whose profile allows no scopes | `400` league `{error:"invalid_scope", …}` — registry accepts `openid`, the profile ceiling refuses; **no** audit row (protocol surface) | ✅ `400 invalid_scope`, no audit row |
| 38 | cc scope inside ceiling | same body on a client whose profile allows `openid` | `200` — token row `scopes:["openid"]` | ✅ `200`, token row `scopes:["openid"]` |
| 39 | refresh after narrowing | mint an `openid` token, narrow the profile to `[]`, then `POST /oauth/token` `{grant_type:"refresh_token", refresh_token, client_id, client_secret}` (no scope param — inherits `openid`) | `400` `{error:"invalid_scope"}` — the subset rule only sees the old token; the live profile decides. Re-widen the profile and the **same** refresh token succeeds (denial fires before rotation; nothing consumed) | ✅ `400 invalid_scope` while narrowed; same refresh token `200` after re-widen (nothing consumed) |
| 40 | exchange outside presenter ceiling | `POST /oauth/token` `{grant_type:"urn:ietf:params:oauth:grant-type:token-exchange", client_id, client_secret, subject_token:<openid token>, subject_token_type:"urn:ietf:params:oauth:token-type:access_token"}` on a presenter whose profile allows no scopes | `400` `{error:"invalid_scope"}` **and** a chained `token.exchanged` deny row with `reason_code:"scope_not_permitted"` bound to the presenting client (unlike legs 37–39, an exchange denial is an authorization outcome) | ✅ `400 invalid_scope` + chained `token.exchanged` row, `reason_code:"scope_not_permitted"`, bound to the presenter |

## 9. Phase 9c legs (ADR-0023) — fail-closed revocation, live ✅ (executed 2026-10-16)

Added in phase 9c: RFC 7009 §2.1 ownership on revoke, deactivation as
a bulk trigger + session-branch lifecycle gate, console-site cache
invalidation parity, D5 `token.revoked` chain rows, tenant-lineage
revocation on membership deactivation, and fail-closed introspection.
Pinned by `FailClosedRevocationTest` 11/11 (full suite 1018/1018).
Setup applied for this run: an access/refresh pair through the customer
client (legs 41–43, 45–46), a second pair through a **different** client
for the foreign legs (41–42), a second tenant's lineage held alive for
47, and a genuine token for the introspection legs (48–50). All ten legs
ran live on 2026-10-16; every expectation held (footnotes carry the
live-shape differences from the automated fixtures).

| # | Leg | Command (shape) | Expected | Observed (live 2026-10-16) |
| --- | --- | --- | --- | --- |
| 41 | foreign access revocation refused | `POST /api/oauth/revoke` with **client B's** credentials + client A's access token | `400` league `{error:"invalid_grant", error_description:…}` — no kill: A's token still authorizes `GET /api/v1/catalog/items` (`200`); no `token.revoked` row for B | ✅ `400 invalid_grant`; A's catalog still `200`; no row for B |
| 42 | foreign refresh revocation refused | same shape with A's refresh token | `400 invalid_grant`; the same refresh token still exchanges (`200`) with A's credentials — nothing consumed | ✅ `400 invalid_grant`; same refresh still exchanges `200` with A |
| 43 | deactivate is a bulk trigger | step-up `POST /api/v1/platform/users/{id}:deactivate` on a target holding tokens + a session row | `200`; target's access rows `revoked=1`, session rows `0`, refresh → `400`; chained rows `reason_code:"user_deactivated"` | ✅ `200 {"data":{…,"status":"deactivated"}}`; access `revoked=1` (0 live), sessions `0`, refresh `400 invalid_grant`; chained `user_deactivated` with kinded `sub:` ref¹ |
| 44 | session gate fail-closed | flip the target's `status` to `deactivated` directly (no endpoint), then any authenticated request as that principal **without** a bearer | `401` — the enforcement point refuses an inactive principal even though no sweep ran (live rows, live session) | ✅ same session-jar request `401` after the direct flip; `200` after restore; 1 live session row throughout (no sweep) |
| 45 | console revoke bites at 0 s | warm the catalog with a bearer (`200`), run `php artisan platform:client:revoke {id}`, retry the same bearer **without** flushing cache | `401` immediately (a stale positive would still answer `200`); chained `reason_code:"client_revoked"` with `actor_client_id` | ✅ `401` immediately after revoke (1 live token killed); chained `client_revoked` with `actor_client_id`³ |
| 46 | console rotation bites at 0 s | warm the jti's status verdict (any authenticated bearer call), run `platform:client:rotate-secret {id}` | verdict entry gone at once — no 30 s wait; chained `reason_code:"secret_rotated"` | ✅ cached verdict `1 → 0` rows at 0 s (1 live token killed, new secret shown once); chained `secret_rotated` with `actor_client_id`³ |
| 47 | membership lineage kill | buyer holding tokens via tenant A's client **and** tenant B's client; OTP `POST /api/v1/customers/profile:deactivate` in tenant A | tenant A's rows revoked + chained `reason_code:"membership_revoked"` with `tenant_ref`; tenant B's rows survive (`revoked=0`) | ✅ `200` deactivated; A: access `revoked=1`, live refresh `0`, membership `suspended`; B: `revoked=0` **and** refresh grant still `200`; chained `membership_revoked` + `tenant_ref` + exact `sub:` ref² |
| 48 | forged signature introspected | `POST /api/oauth/introspect` with a genuine token whose signature segment has one flipped character (real jti behind it) | `{"active": false}`; the untouched original still answers `{"active": true}` — the denial is the signature check, not breakage | ✅ `{"active": false}`; original still `{"active": true}` |
| 49 | dead owner introspected | introspect a live token (`active:true`), suspend the owner directly (rows intact), introspect again | `{"active": false}` — introspection agrees with dual-accept | ✅ second introspect `{"active": false}` after the direct suspend |
| 50 | null expiry introspected | set a live row's `expires_at` to `NULL`, introspect | `{"active": false}` — no bounded lifetime can never be active | ✅ `{"active": false}` |

1. Live shape differs from the test fixture (`actingAs`): `/api/v1/*` is
   bearer-only (`SessionContextForReads` hydrates GET/HEAD, never
   mutations), so the issuer authenticated with a fresh OIDC bearer
   minted for the session-holding user. `RequiresAcr:2` was satisfied by
   `users.mfa_enabled=1` + an `mfa_step_ups` row verified within 15 min,
   and `platform:user:manage` came from a SQL-seeded capability chain
   (`roles` → `role_permissions` → `membership_role_assignments`).
2. Live issuance divergence: `POST /api/v1/customers/auth/otp` returns
   `404` on the federated runtime — `LegacyLoginSurfaceGate` closes the
   legacy customer login surface in every federated deployment (the §5
   gap "deactivate-by-OTP is dead in federated mode"). The
   `purpose=login` challenge was minted through the real `requestOtp`
   endpoint on a **direct-mode twin** `artisan serve` (`:8001`, same e2e
   DBs); `profile:deactivate` itself is ungated and consumed it on the
   federated process.
3. Legs 45–46 ran against a dedicated "Scoreboard Console Victim II"
   client created for the run (secret rotated, then client revoked
   during the leg). Cache rows are plaintext (`authz:tok:<jti>`) with a
   ~30 s TTL, so the 0 s probes were taken immediately after the console
   command.

## 10. Back-channel fan-out drill (A-SEC-004-6) — live ✅ (executed 2026-10-17)

The full RP-side back-channel loop end to end against the rebound
e2e stack (issuer `http://localhost:8000` on MySQL `navdhya_e2e_*`,
customer BFF `next dev` on `:3001` in federated mode). Every behavior
is pinned by the automated battery (`tests/identity/backchannel-logout.test.ts`
67/67); these legs prove the wiring against real processes, real
MySQL, and real HTTP fan-out.

| # | Leg | Observed (live 2026-10-17) |
| --- | --- | --- |
| 51 | Login → consent → BFF callback | `nv_customer_token` cookie set; the AT's payload carries `sid` byte-equal to the `sid` in an `id_token` minted by a second PKCE exchange under the same session — sid is session-wide, not per-grant |
| 52 | Pre-state | `oidc_session_bindings`: 1 row for the sid (`notified_at` NULL); `oidc_backchannel_deliveries`: 0 rows |
| 53 | RP-initiated logout (`GET /oauth/logout?id_token_hint=…`) | `200` "Signed out" — an **unregistered** `post_logout_redirect_uri` renders the local sign-out page, never a redirect (open-redirect-safe by design) |
| 54 | Post-state | all 3 AT rows for the sid `revoked=1`; their refresh lineage revoked; binding `notified_at` stamped |
| 55 | Fan-out delivery | outbox row targets the BFF receiver; the inline attempt hit `cURL error 28` (Next dev's first-hit route compile exceeded the 3 s budget); `auth:retry-backchannel-logout --force` delivered it `204`, row → `state=delivered` |
| 56 | Kills observed | BFF route with the dead cookie → `307` to login (RP-local sid denylist); `GET /oauth/userinfo` with either revoked AT → rejected at the issuer; refresh grant → `400 invalid_grant` "Token has been revoked" |
| 57 | Multi-RP binding | partner BFF authorize → consent → callback under the **same** issuer session: `oidc_session_bindings` holds 2 rows for one sid (customer `01a13c33…`, partner `01a14806…`), both `notified_at` NULL |
| 58 | Fan-out to N=2 | one `GET /oauth/logout?id_token_hint=…` → **two** delivery rows; partner delivered inline `204` (attempt 1, receiver pre-warmed), customer's inline attempt hit the cold-dev-server timeout again and was delivered by `auth:retry-backchannel-logout --force` (attempt 2, guard line `federation.issuer: http://localhost:8000` printed) — both the inline and outbox paths exercised in a single logout |
| 59 | Both RPs dead at once | customer `/api/profile` and partner `/api/temples` with their (dead) session cookies → both `401` (RP-local sid denylists); userinfo with the **partner's** AT → rejected at the issuer; refresh grant → `400`; AT lineage 3/3 `revoked=1`, 0 live refresh rows |
| 60 | Post-logout redirect (registered) | `post_logout_redirect_uris` set to the app root on all four BFF profiles (out-of-band profile update — the pre-existing e2e clients predate the create-time `--post-logout-redirect-uri` flag); then `GET /oauth/logout?id_token_hint=…&post_logout_redirect_uri=http://localhost:3001/&state=drill3` | `302` straight to `http://localhost:3001/?state=drill3` — registered URI accepted, `state` round-trips byte-exact, no interstitial; the fan-out still fires alongside the redirect (delivery row drained via the outbox, `attempts=2`); AT/RT lineage `revoked`. Contrast with leg 53: an **unregistered** URI never redirects — it renders the local sign-out page |

**Operational rules learned:**

- **Warm the RP receiver before a drill** (one garbage POST — it must
  answer a fast uniform `400`): the inline attempt has a 3 s budget and
  a cold dev server exceeds it; the outbox retry is the designed
  fallback, not a failure. Observed twice — a dev server can go cold
  again between drills (leg 58), so warm every receiver in every
  session, not once ever.
- **`auth:retry-backchannel-logout` must run with the same
  `AUTH_MODE`/`AUTH_DOMAIN`/`AUTH_ISSUER` environment as the serving
  issuer.** The re-minted token carries `config('federation.issuer')`
  as `iss`, which the RP byte-compares; a CLI process launched without
  the AUTH_* overrides mints `iss=http://localhost` (the checked-in
  `.env`) and every RP rejects with a uniform `400`, burning backoff
  windows. The command now prints the issuer it mints with on every
  run so the mismatch is visible before deliveries fail.
- **Logout tokens travel as `application/x-www-form-urlencoded`**
  (`logout_token` param — the dispatcher's `Http::asForm()` shape); the
  receivers accept exactly that content type. A raw `application/jwt`
  body answers the uniform `400`.
- **The serving issuer needs the full drill environment, not just
  `AUTH_*`:** `CENTRAL_DB_DRIVER=mysql` + `CENTRAL_DB_DATABASE=navdhya_e2e_central`
  and the `TENANT_DB_*` twins (the checked-in `.env` points `central`
  at sqlite — a server or CLI process booting from it alone sees an
  empty `oauth_client_profiles` table and every RP resolves to nothing),
  plus `IDENTITY_HMAC_SECRET` (canonical e2e value pinned in
  `e2e/run.ps1`). Without the secret, every `POST /auth/login/verify`
  dies `500` inside `AuthEventRecorder` (D5 fails closed): the OTP
  challenge is consumed but no session is minted, so the breakage
  surfaces as a generic internal error two hops upstream of anything
  logout related.

### Legs 61–67 — RP-initiated logout through the BFF's own surfaces — live ✅ (executed 2026-10-17)

The last A-SEC-004-6 gap closed: the loop now runs start → OP
end-session → landing through the customer BFF's own routes, never
driving `/oauth/logout` directly. One cookie jar end to end;
`nv-logout-state` registration added out-of-band to all four e2e
profiles alongside the app roots.

| # | Leg | Observed (live 2026-10-17) |
| --- | --- | --- |
| 61 | BFF logout start | `GET :3001/api/auth/logout/start` (jar) | `307` + `Cache-Control: no-store`; Location = issuer `/oauth/logout?client_id=01a13c33…&post_logout_redirect_uri=http%3A%2F%2Flocalhost%3A3001%2Fapi%2Fauth%2Flogout%2Flanding&state=<32 B base64url>`; `set-cookie: nv-logout-state=<same state>; Path=/; HttpOnly; SameSite=Lax; Max-Age=600`; no token material anywhere on the URL |
| 62 | OP end-session (bare `client_id`) | `GET` the 307 Location (jar) | `302` straight to the registered landing, `state` byte-exact; the session terminated, its binding stamped and its fan-out fired inside the same request |
| 63 | Landing, correlated | `GET …/api/auth/logout/landing?state=…` (jar) | `200` "You are signed out", `Cache-Control: no-store`; **both** `nv_customer_token` and `nv-logout-state` cleared `Max-Age=0`; the jar afterwards holds only the OP's own `laravel_session` |
| 64 | Post-state | tinker (MySQL) | new binding `notified_at` stamped same-second; outbox row `state=delivered, attempts=1, delivered_at` same-second (receiver pre-warmed — inline path, no retry needed); AT row `revoked=1` |
| 65 | Kill — RP denylist | `GET :3001/api/profile` with the dead cookie, seconds after the landing | `401` — the in-process sid denylist kills instantly |
| 66 | Kill — issuer userinfo, **both sides of the D2 bound** | `GET /oauth/userinfo` with the revoked AT: immediately after the landing, then >30 s later | `200` inside the bound — `SessionTerminator`'s lineage kill is a raw `revoked=1` update (no `invalidateTokens`), so the positive verdict cached pre-logout survives to the TTL — then `401` once the documented ≤30 s staleness ceiling passes. The hard bound is the contract; legs 56/59 observed the post-bound side (outbox retries pushed their kills past the window), this leg pins both sides in one run |
| 67 | Forged landing link | `GET :3001/api/auth/logout/landing?state=forged-state-entirely` (clean jar) | `307` to `/` — cleanup still runs (both cookies cleared `Max-Age=0`), the signed-out acknowledgment never renders |

### Legs 68–72 — full N=4 fan-out across all four BFFs — live ✅ (executed 2026-10-17)

The scale test: web (`next dev :3000`) and dashboard (`next dev :3003`)
booted in federated mode for the first time and joined customer
(`:3001`) and partner (`:3002`). One issuer session, four bindings, one
RP-initiated logout through customer's own start route — every RP must
die at once. Drill script: `Temp\opencode\drill-n4.ps1`.

**Pre-flight registration findings (fixed out-of-band, same pattern as
the landing-URI additions):**

- Web and dashboard client secrets had never been recorded anywhere
  (shown once at creation, creation output not retained) — both
  rotated via `platform:client:rotate-secret` (0 live tokens killed;
  the old secrets were unusable anyway, so this is a clean break, not
  a overlap). The BFFs were then launched with the new secrets as
  process env, leaving their `.env.local` files untouched.
- Dashboard's `redirect_uris` was mis-registered to
  `http://localhost:3001/api/auth/callback` — the **customer** BFF's
  callback. Fixed to `:3003`. (Never surfaced before: dashboard had
  never completed an authorize flow.)
- Dashboard's profile carried `require_par=true` (the N2 PAR-mandate
  cohort) while its three `first_party_web` siblings did not. The OP
  correctly answered the RFC 9126 denial —
  `error=invalid_request`, *"This client must push its authorization
  request before redirecting a user agent"* — because no BFF implements
  PAR push. The OP behavior was right; the registration was wrong.
  `require_par` set to `false` to match the cohort.

| # | Leg | Observed (live 2026-10-17) |
| --- | --- | --- |
| 68 | Four-way binding on one session | customer login (OTP test short-circuit) → authorize/callback; then partner, web, dashboard each `GET :300X/api/auth/authorize?redirect=/` → OP (session already live) → auto-approve (partner/web, remembered grants) or consent + approve (dashboard, first consent) → callback | all four session cookies set (`nv_customer_token`, `__session`, `navdhya-session`, `nv_dashboard_token`, 1056 B each); `oidc_session_bindings` holds 4 rows under **one** sid |
| 69 | Warm four receivers | garbage `POST` to all four `/api/auth/backchannel-logout` | `400 ×4` uniform |
| 70 | One RP-initiated logout, N=4 fan-out | `GET :3001/api/auth/logout/start` → OP end-session → landing | `307` + `nv-logout-state`; OP `302` to the landing with `state` **byte-exact**; landing `200` "signed out" with both cookies cleared `Max-Age=0`; the OP's inline fan-out fired inside the end-session request to **four** registered receivers |
| 71 | Post-state | tinker (MySQL) | all 4 bindings `notified_at` stamped, `all four same sid: YES`; **4 delivery rows `state=delivered, attempts=1`** — every receiver warm, so the entire fan-out stayed inline (no outbox, no retry) |
| 72 | Four RP kills at once | dead cookies presented explicitly to each app's choke-point-guarded route: customer `/api/profile`, partner `/api/temples`, web `/api/sessions` (`wiredActor`), dashboard `/api/auth/me` (`verifiedSessionClaims`) | `401 ×4` — one logout, four in-process denylists, zero survivors |

**Operational rules learned:**

- **Boot web/dashboard with the federation env as process env and
  verify before drilling:** `CUSTOMER_AUTH_*` (web) / `DASHBOARD_AUTH_*`
  (dashboard) — `MODE=federated`, `ISSUER`, `CLIENT_ID`,
  `CLIENT_SECRET`. The readiness probe is
  `GET :300X/api/auth/logout/start` — a `307` to the issuer proves the
  wiring; a `404` means the app is running direct mode (its
  `isFederated()` gate), and a first background shell boot was observed
  to silently lose its env assignments while an identical re-boot kept
  them. Always probe; never assume.
- **Tinker does not alias the back-channel models by short name:**
  use `App\Platform\Identity\OAuth\OidcSessionBinding` and
  `App\Platform\Identity\OAuth\OidcBackchannelDelivery` (FQCN).

### Legs 73–77 — OIDC prompt enforcement (A-SEC-004-7) — live ✅ (executed 2026-10-17)

Full `prompt` support at the authorize endpoint, closing the last
implementable deliberate gap of A-SEC-004-5/6. Passport implements
the semantics of `none`/`login`/`consent` natively; the platform
added the VALIDATION half it does not do (`EnforcesPrompt`, attached
between the PAR rewrite and `EnforcesMaxAge`) and pinned every
outcome. Driven directly against the issuer with a live OP session
(login via the customer BFF, fresh PKCE per request); drill script:
`Temp\opencode\drill-prompt.ps1`. Automated pin:
`PromptEnforcementTest` (11/11).

| # | Leg | Observed (live 2026-10-17) |
| --- | --- | --- |
| 73 | `prompt=none`, active grant (customer) | `GET /oauth/authorize?…&prompt=none` | `302` straight to the registered callback **with `code`** + `state` + `iss` — the SSO silent flow: no consent page, no interaction, grant remembered (the BFF's active AT from the setup bind) |
| 74 | `prompt=none`, no grant (web) | same request against web's client | `302` `error=consent_required` + `state` + `iss` — interaction is forbidden, so the missing grant is reported, never a consent page the silent RP cannot see |
| 75 | `prompt=login`, fresh session | `prompt=login` on a session authenticated seconds earlier | `302` to `/auth/login` — the OP session is destroyed and the browser handed to the unified ceremony; session freshness is irrelevant, the user must authenticate **again** (`promptedForLogin` loop-guard parked) |
| 76 | `prompt=consent`, active grant | re-login + re-bind (grant active again), then `prompt=consent` | `200` consent screen — the same request auto-approves silently without the parameter (leg 73's shape); the forced screen wins over the remembered grant |
| 77 | Invalid prompts | `prompt=none login` and `prompt=select_account` | both `302` `error=invalid_request` to the registered callback with `state` + `iss` — `none` combined with anything else is OIDC Core's MUST-reject; unknown values are loud-rejected (fail-closed: an unimplemented interaction type never degrades into a plain flow). Array injection (`prompt[]=…`) and the unconditional scope (validated on non-openid requests too — prompt needs no OIDC claims, unlike max_age) are pinned in the battery |

**Operational rules learned:**

- **Laravel's global `ConvertEmptyStringsToNull` normalizes
  `prompt=` to `null` before route middleware sees it** — an empty
  prompt must be treated as the absent parameter (`null`/`''` both
  pass through), not as a malformed value. The battery pins both
  shapes (`prompt=` live, array injection rejected).
- **Passport's `prompt=login` is a full session destroy** (guard
  logout + `session->invalidate()`), not a soft re-auth challenge —
  anything parked in the pre-bounce session is gone afterwards; the
  `promptedForLogin` flag rides the post-invalidate session and is
  consumed on the resume.

### Legs 78–80 — PAR federation push (A-SEC-004-8) — live ✓ (executed 2026-10-17)

The N=4 fan-out proved the issuer *denies* front-channel requests from
mandate clients (`require_par`, the N2 cohort) but surfaced that no
BFF could *satisfy* the mandate. These legs boot the customer BFF with
`CUSTOMER_AUTH_PAR=required` (process env; the customer profile's
`require_par` flipped to `true` for the drill and restored afterwards)
and run the full pushed flow. Drill script:
`Temp\opencode\drill-par.ps1`. Fleet battery:
`tests/identity/par-push.test.ts` (36/36).

| # | Leg | Observed (live 2026-10-17) |
| --- | --- | --- |
| 78 | BFF login under the mandate, end to end | `GET :3001/api/auth/authorize` → `302` to `/oauth/authorize?client_id=…&request_uri=urn%3Aietf%3Aparams%3Aoauth%3Arequest_uri%3A…` — ONLY the RFC 9126 §4 pair; guest bounce → `/auth/login` (no consumption yet), the login page pulls the parked URL into `next`, OTP ceremony, authenticated resume consumes the capability, consent over the expired grant, code → callback → `302 /` + session cookie, `/api/profile` `200` |
| 79 | Mandate holds for direct front-channel | fresh valid PKCE authorize with every parameter in the query (no `request_uri`) → `302` to the registered callback `?error=invalid_request&error_description=This client must push…&state=…&iss=http%3A%2F%2Flocalhost%3A8000` — the established denial shape |
| 80 | Replayed `request_uri` fails closed | re-GET of the consumed authorize URL → `400` `{"error":"invalid_request","error_description":"The request_uri has already been used."}` — single-use, league shape, never a redirect |

**Operational rules learned:**

- **The resume rides `next`, and it is single-use**: the login page
  pulls `url.intended` (the bounced authorize URL) into the ceremony's
  `next` at render, and the JS posts it back at verify
  (`login.blade.php`). A drill — or client — that re-GETs
  `/auth/login` for a CSRF token without re-bouncing through the
  authorize URL first burns the stash, and the verify falls back to
  `/` (the first run lost the `request_uri` exactly this way).
- **`{PREFIX}_AUTH_PAR` is strict**: `off` | `required` only; anything
  else throws at first use, exactly like `AUTH_MODE`. Under
  `required` a failed push is a 500 — the BFF never degrades to
  front-channel parameters. The TTL bound is the issuer's 120 s
  (`PushedAuthorizationRequest::TTL_SECONDS`): the ceremony must
  finish inside it or the resume dies at the OP and login restarts.
- **PowerShell drill variables are case-insensitive**: `$CB` and
  `$cb` are one variable. The first run's leg 79 clobbered the
  registered callback URI with leg 78's result variable, producing a
  spurious `400` (the empty-`redirect_uri` denial branch). Name loop
  variables apart.
- **`403 membership_inactive` is data, not auth**: the profile leg
  first answered `403` — customer 3's `tenant_memberships.state` was
  `suspended` (row last updated 2026-10-16, before this slice). The
  AT authenticated and the tenant matched; the membership gate did
  its job. Restored to `active`, profile `200`.
- Restore: `require_par=false` for all four profiles (sibling-consistent
  again), customer BFF re-booted without the PAR env — the probe shows
  the classic front-channel authorize URL once more.

## 13. DPoP sender-constrained tokens (A-SEC-004-9) — live ✓ (executed 2026-10-17)

RFC 9449 end to end against the real issuer (mysql e2e central, `CACHE_STORE=database`
— replay state survives across requests). Drill scripts:
`Temp\opencode\dpop.mjs` (key/proof helpers: P-256, ES256, IEEE P1363,
RFC 7638 thumbprint with braces) and `Temp\opencode\dpop-legs.mjs`
(legs 81–84 + `recover`), `Temp\opencode\dpop-bff-leg.mjs` (leg 85).
Fleet battery: phpunit `tests/Platform/Identity/DpopBindingTest.php`
(21) + root vitest `tests/identity/dpop-binding.test.ts` (12).

| # | Leg | Observed (live 2026-10-17) |
| --- | --- | --- |
| 81 | Bind at the code exchange | full OTP ceremony → `POST /oauth/token` with a `DPoP` header → `200`, decoded AT carries `cnf.dpop_jkt` equal to the presented key's RFC 7638 thumbprint |
| 82 | Resource accepts a live proof | `GET /oauth/userinfo` + bound AT + proof (`ath`) → `200` with `sub` |
| 83 | Resource denials | no `DPoP` header → `401` envelope `error.code=invalid_dpop_proof` + `WWW-Authenticate: DPoP error="invalid_dpop_proof", algs="ES256, RS256"`; a DIFFERENT key → `401` "The DPoP proof key does not match the access token binding."; the SAME proof twice → first `200`, second `401` "jti has already been used (replay)" (database cache, cross-request) |
| 84 | Mandate fails closed, code survives, same-jkt refresh | `require_dpop=true`: exchange WITHOUT proof → league `400 {"error":"invalid_dpop_proof"}`, the SAME code WITH proof → `200` (never consumed); refresh without proof → `400`; refresh with a different key → `400` (no rotation happened); refresh with the same key → `200` + new AT still bound; the leg-81 AT still refuses proofless calls after the mandate flipped on and back off; restored `require_dpop=false` |
| 85 | BFF end to end under the mandate | profile `require_dpop=true`, full customer drill through `:3001` → authorize bounce → OTP ceremony → consent → code → callback exchange (BFF's DPoP proof satisfies the mandate) → `302` + `nv_customer_token`; `GET /api/bookings` (laravel-backed upstream) → `200` — the bound AT + live proof crossed BFF→issuer |
| r | Recovery after restore | `require_dpop=false` → bare exchange (no `DPoP` header) → `200`, mint has NO `cnf` claim — pre-slice behavior byte-identical |

**Operational rules learned:**

- **`nonce` is required with the `openid` scope**: a PKCE authorize
  without it → `400 invalid_request` with hint "A nonce is required
  when the openid scope is requested." — the drill's first run died
  here (the BFF always sends one; curl replicas must too).
- **Never page-scope the consent parse**: `oauth/authorize.blade.php`
  renders approve and deny forms side by side, and the deny form
  carries `@method('DELETE')` as a hidden input. A page-wide
  hidden-field repost smuggled `_method=DELETE` into the approve POST
  — Laravel method spoofing routed it to the DENY handler and the
  callback came back `error=access_denied` ("The user denied the
  request"). Scope the parse to the form that has `_token` and no
  `_method`.
- **`var next` is JSON, not a JS literal**: the login page emits
  `var next = "…"` with `\/` and `\u0026` escapes — a single-quote
  regex finds nothing; parse the quoted value as JSON.
- **Second ceremony for the same phone needs OTP state cleared**:
  `otp-policy` `fresh_retry_after=600` lives in the application cache
  while the challenge rows live in central
  (`customer_otp_challenges WHERE identifier=…`) — both must go
  between ceremonies or the second OTP request answers
  `429 rate_limited` (the route throttle alone was a red herring).
  Drills: `php artisan cache:clear` + the documented row DELETE.
- **Two error shapes at once**: resource denials (`/oauth/userinfo`,
  `/api/v1/*`) use the platform envelope (`error.code=…`), the token
  endpoint uses league's flat (`{error, error_description}`) — assert
  both shapes distinctly, the first run's "failures" were the wrong
  shape, not the wrong behavior.
- **Federation boot is process env** (checked-in `.env` has none of
  it): `AUTH_MODE=federated`, `AUTH_DOMAIN=localhost` (exact-host
  gate — the bounce to `/auth/login` never fires without it, and the
  drill silently lands on the admin `/login` instead), and
  `AUTH_ISSUER=http://localhost:8000` byte-equal to the BFF's
  `CUSTOMER_AUTH_ISSUER` (RFC 9207 check is exact).
- **Refresh rotation does not retire access tokens** (they are
  TTL-bound — A-SEC-004-6's contract), but every minted AT stays
  key-bound regardless of later mandate flips: binding lives in the
  token's `cnf`, not the profile row.
- The DPoP migration is now idempotent (`hasColumn` guards): the e2e
  central had been partially migrated out-of-band and re-running
  `php artisan migrate` died on `Duplicate column name 'dpop_jkt'`
  forever; guarded, it applied, recorded, and re-runs cleanly.

## 14. Refresh lineage sid continuity (A-SEC-004-10) — live ✓ (executed 2026-10-17)

The hole every slice since A-SEC-004-6 had flagged: refresh rotation
minted sid-less successors, so a family rotated before logout walked
outside the terminator's `where sid =` net. Drill script:
`Temp\opencode\sid-legs.mjs` (ceremony copied from the DPoP drill).
Fleet battery: `tests/Platform/Identity/AccessTokenSessionBindingTest.php`
(7: the 3 A-SEC-004-6 cases plus 4 new rotation cases — all 4 new
ones failed against the pre-fix grant).

| # | Leg | Observed (live 2026-10-17) |
| --- | --- | --- |
| 86 | Sid rides the successor | openid ceremony → AT1 `sid` claim → refresh → AT2 `sid` === AT1's → second rotation → AT3 unchanged (the whole family under one lineage) |
| 87 | Kill reaches the rotated lineage | fresh family, rotate FIRST, then `POST /auth/logout`: successor AT → `401` at userinfo (pre-fix it answered `200`), kept refresh token → `400 invalid_grant`; the OTHER session's family (different sid) survives the logout itself (`200`), then dies to the denied refresh's deliberate user+client reuse kill (SEC-003 §3.2) |
| 88 | DPoP × sid across rotation | bound family (proof at exchange + refresh): successor keeps `cnf.dpop_jkt` AND the same `sid` — both stamps from the one entity stamp |

**Operational rules learned:**

- **Order the survivor assertion BEFORE the denied refresh**: the kill
  is sid-scoped, but presenting the killed family's refresh token
  fires reuse detection, which is user+client-wide by design
  (`RotatingRefreshTokenRepository::revokeLineage`) — a probe taken
  after that 400 looks like the LOGOUT killed everything, and the
  first run of this drill "failed" on exactly that misreading.
- **OP logout from a drill needs no page fetch**: the session cookie
  jar already holds `XSRF-TOKEN`; posting `x-xsrf-token` with the
  URL-decoded cookie value satisfies the CSRF gate on
  `POST /auth/logout`.
- Two full-suite runs flaked on `openssl_pkey_new` inside
  `BuildsDpopProofs` (DPoP test keygen) at DIFFERENT tests — a
  transient on this box's PHP 8.5.6/openssl build (a behavioral
  regression cannot fail two different tests on the same keygen
  line). The helper now retries the keygen 3× with a 50 ms pause and
  still fails loudly (with the last openssl error) if it never
  succeeds — the filtered runs (28/28, 49/49) and the pre-guard
  isolated runs (21/21) were always green.

## 15. BFF refresh rotation (A-SEC-004-11, D4 browser half) — live ✓ (executed 2026-10-18)

The last §5 auth gap: the customer BFF discarded the refresh token
at login, so every 900 s access-TTL expiry forced a full re-login.
The BFF now holds the refresh token in a SECOND HttpOnly cookie
(`nv_customer_refresh`, 30-day max-age) and rotates the family
lazily in `proxy.ts` — Next 16's replacement for `middleware.ts`
(the proxy runs the node runtime, which `node:crypto` DPoP needs) —
rewriting the incoming request's cookie header with the fresh pair
so no downstream handler ever presents a consumed token. Drill
scripts: `Temp\opencode\refresh-legs.mjs` (legs 89–92),
`Temp\opencode\refresh-probe.mjs` (the leg-90 diagnosis). Fleet
battery: root vitest `tests/identity/refresh-rotation.test.ts` (25)
plus3 key-store cases in `dpop-binding.test.ts`.

| # | Leg | Observed (live 2026-10-18) |
| --- | --- | --- |
| 89 | Login stores the refresh cookie | full ceremony through `:3001` → callback sets `nv_customer_token` (900 s) AND `nv_customer_refresh` (second HttpOnly cookie); an authenticated laravel-backed route answers `200` |
| 90 | Near-expired AT rotates server-side | craft the AT with `exp = now − 10` (the BFF decodes signature-free) → `GET /api/profile` → `200` with REAL upstream data; fresh AT (900 s) + fresh RT (30 d) cookies replace the old pair; the parked-key cookie is cleared; the predecessor RT is one-time-use |
| 91 | Idle browser resurrects | delete the AT cookie, keep only the RT cookie → `GET /api/profile` → `200` — rotation with no access cookie present re-issues both cookies (status `resurrected`) |
| 92 | OP logout kills the rotated lineage | `POST /auth/logout` at the issuer → next BFF call `401` envelope `UNAUTHENTICATED` (sid denylist); a post-expiry rotation → `400 invalid_grant` → **both cookies cleared** (`Max-Age=0`); the jar holds no session cookies; the retired RT answers `invalid_grant` at the issuer |

**Operational rules learned:**

- **The DPoP key must be ONE per instance across Next's module
  graphs.** The first drill run: every rotation died `400
  invalid_dpop_proof` — the callback (route graph) bound families to
  ITS copy of `dpop.ts`'s key, while the proxy (a SEPARATE module
  graph under Turbopack) proved with its own copy. `server/dpop.ts`
  now persists the instance key to a file (`$CUSTOMER_DPOP_KEY_FILE`,
  default `<cwd>/.next/dpop-key.json`, `O_EXCL` claim +
  reload-on-miss); per-graph caches are only a fast path over that
  file. Consequence worth knowing: a process RESTART no longer drops
  the binding key — sessions survive restarts; only a wiped or
  unreadable key file forces re-login (the old restart semantics).
- **Fatal vs deferred are distinguishable by the cookie shape**: a
  4xx at the token endpoint (`invalid_grant` — lineage dead — or
  `invalid_dpop_proof` — lost key) clears BOTH cookies
  (`Max-Age=0`); the browser lands on the normal re-auth path. A
  5xx/transport failure parks the idempotency key (`nv_customer_rot`,
  60 s) and touches neither credential — the next request replays it
  (issuer idempotency + single-flight make the double-rotation — a
  SEC-003 §3.2 lineage kill — unreachable). The BFF logs the OAuth
  error code (`[session-refresh] rotation refused: 400
  invalid_dpop_proof`), never token material.
- **The issuer's inline throttle is ONE per-IP counter**: every
  `throttle:N,M` route keys `sha1(domain|IP)` with NO route
  component (prefix-less `ThrottleRequests` signature), so a dense
  drill minute (ceremony + upstream calls + rotations) trips the
  strictest limit (10/min on `login/otp` → `429 rate_limited`). The
  429 check itself does not increment — waiting 62 s and retrying
  once always clears the window; the drill script does this
  automatically. The OTP manager's own gates (fresh-challenge +
  hourly cap) are separate state: challenge rows + application cache
  (`php artisan cache:clear` + the documented row DELETE).
- **`redirect: 'manual'` on every drill request**: a login redirect
  followed by fetch reads as a `200` HTML page with a null body —
  one early leg-91 "failure" was the redirect being followed, not
  the rotation.
- **Restart matrix (this slice's net change)**: BFF restart → live
  sessions continue (file-backed key, parked-key cookie replays);
  issuer restart → token state is DB-backed, nothing dies; key-file
  wipe (e.g. `.next` clean) → the only re-login trigger.
