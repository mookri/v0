# ADR-0021: Authorization-code hardening — PAR, RFC 9207 `iss`, OIDC, per-client scope ceiling

**Status:** Accepted · **Date:** 2026-10-15 · **Deciders:** Platform Architect

## Context

ADR-0020 established the federation surface (auth subdomain, six binding
decisions) and phase 8 cut the BFFs over to it. Phase 9a hardens the
authorization-code path against the attacks those decisions leave
residual: request smuggling through the browser channel (fixable with
RFC 9126 pushed authorization), response mix-up across authorization
servers (fixable with RFC 9207 `iss`), session-fixation and replay of a
minted identity assertion (fixable with OIDC `nonce` + ID-token binding),
and a scope that silently widens when a client's profile is narrowed
after a code is minted (fixable with a fail-closed, per-client scope
ceiling enforced at every auth-code seam).

This ADR supersedes nothing in ADR-0020; it extends the authorization
surface under the same issuer, key custody and audit posture. The one
ADR-0020 finding it corrects is recorded in *Supersession* below.

The phase ships as five tasks (A1–A5), each with a protocol contract, a
failure mode, a negative test and an enforcement seam. A decision
without a green enforcement test is not implemented.

## The binding decisions

### D1 — Pushed authorization is a per-client mandate, never a global one

RFC 9126: a confidential client pre-validates its full authorization
request over an authenticated back channel (`POST /api/oauth/par`) and
receives a single-use, short-TTL `request_uri` (120 s) to present at the
browser-facing authorize endpoint. The push endpoint mirrors the
authorize endpoint's validation one-for-one — response type, redirect
URI, PKCE S256, openid-nonce, **and the A5 scope ceiling** — so a pushed
request replays without surprises and an invalid one dies at the push,
never as a browser redirect.

- **N2 clients require PAR.** `OAuthClientProfile.require_par` gates the
  authorize endpoint via `RequiresPushedAuthorizationRequest`: dashboard,
  admin, sensitive-partner and delegated-API clients are rejected without
  a valid `request_uri`. **N1 clients** (customer-bff, web-bff) may opt
  in; they are never forced.
- **Discovery advertises the endpoint but never a server-wide mandate.**
  `require_pushed_request_parameters` is deliberately omitted from both
  documents — advertising it would misdescribe N1 clients that never
  push.
- Client authentication at the push is `client_secret_post` or
  `private_key_jwt`; a strict param allowlist rejects unknowns; the
  response is `201 {request_uri, expires_in:120}`.

*Enforcement:* `PushedAuthorizationRequestTest` (replay, expiry, client
mismatch, unregistered redirect, unknown param, bad challenge method,
missing nonce, cross-client assertion, **push-seam scope guard** — 14
legs) and `ParEnforcementTest` (N2 mandate, resume, audit — 9 legs).

### D2 — Every redirect-based authorization response is issuer-bound (RFC 9207)

Every redirect-based authorization response — approval, consent denial,
`prompt=none` `login_required`, and league protocol errors that carry a
verified redirect — appends the issuer identifier as an `iss` query
parameter, byte-identical to discovery and the ID token `iss`. A BFF
callback **requires** the exact match before it exchanges the code
(response mix-up defence).

- `AppendsIssuerToAuthorizationResponse` attaches to all three authorize
  routes (GET/POST/DELETE). It splits the fragment first, then the query,
  and appends `iss` **only when the top-level query actually carries a
  protocol outcome** (`code` or `error`). The federation login bounce (a
  `redirect` query only, no code/error) is never issuer-bound.
- A `prompt=none` `login_required` is delivered to the registered
  redirect URI (never bounced to the login page) and is still an
  issuer-bound protocol response.

*Enforcement:* `AuthorizationResponseIssuerTest` (approval, denial,
`prompt=none`, login-bounce non-outcome — 4 legs).

### D3 — OIDC Authorization Code flow: mandatory nonce, ID token only from the token endpoint

Code flow only; `response_type=code`. When the `openid` scope is
requested:

- **Nonce is mandatory and exactly validated.** Non-empty, bounded at
  512 characters, checked in `OidcAuthCodeGrant::validateAuthorizationRequest`
  *after* the parent validation and *after* the A5 scope guard (scope
  rejection wins over nonce). A missing or over-long nonce is a league
  `invalid_request` 400 JSON with a hint and **no `Location`** — the
  request never becomes a partial authorization.
- **The ID token is minted only by an openid code exchange** at the token
  endpoint — never by refresh, never by `client_credentials`. It is
  RS256-signed, carries a `kid` resolvable against the published JWKS,
  and its signature verifies with the published public key. Claims:
  `iss` = shared issuer, `aud` = the code's client, `sub` = the account's
  stable identifier (userinfo parity), `nonce` round-trip, `exp − iat =
  600`, plus ceremony facts.
- **Ceremony facts** (`auth_time`, `amr`, `acr`, `sid`) are read from the
  session the federation login wrote. A session established without the
  ceremony (test harness, pre-9a) mints a safe fallback (`auth_time` =
  now, a per-session `sid`, **no `amr`/`acr` — absent means absent**,
  never invented). The federation login writes `amr` in IANA registry
  values (`otp`/`pwd`/`mfa`), `acr` in the audit chain's `aal`
  vocabulary, and a fresh ULID `sid` — never the raw session id.
- **Concurrent-tab displacement fails closed at the BFF.** The nonce
  stash is keyed per client, so the last-validated nonce wins; a BFF that
  stored an earlier tab's nonce must reject the displaced token.

*Enforcement:* `OidcIdTokenTest` (nonce missing/overlong/bounded, RS256
+ kid + signature + shared issuer, full OTP-ceremony binding, seeded
ceremony facts, no-ID-token-on-non-openid, tab displacement, authorize +
token endpoint scope seams — 10 legs).

### D4 — Client profiles (N1 vs N2) and grant boundary

- **N1:** customer-bff, web-bff.
- **N2:** dashboard, admin, sensitive-partner, delegated-API.
- **F8:** RS256 is the sole ID-token signing algorithm.
- **F9:** the FAPI refresh lifecycle is deferred to phase 9g.

### A5 — Per-profile `allowed_scopes` ceiling (fail-closed, three seams)

`EnforcesProfileScopes` is the single rule shared by the three auth-code
enforcement seams so it can never drift between them:

| Seam | Location | Failure shape |
| --- | --- | --- |
| Authorize | `OidcAuthCodeGrant::validateAuthorizationRequest` | league `invalid_scope` 302 redirect (+ state, then `iss`) |
| Token exchange | `OidcAuthCodeGrant::respondToAccessTokenRequest` | league `invalid_scope` 400 JSON, no redirect |
| PAR push | `PushedAuthorizationRequestController::validateProfileScopes` | `invalid_scope` 400 JSON, no redirect |

- **Fail closed:** a missing profile row or an empty `allowed_scopes`
  means nothing may be requested — registering a scope in Passport's
  registry never implicitly grants it to every client, and a deleted
  profile revokes entitlement rather than widening it.
- **An empty requested scope always passes** — the ceiling gates what MAY
  be requested, not whether a token may be minted with no scopes at all
  (pre-9a `scope=` flows stay untouched).
- **Boundary (documented, not yet guarded):** `client_credentials`,
  `refresh_token` and token-exchange grants are **not** guarded by A5 in
  9a — they mint no ID tokens and never cross these three seams. This
  boundary is intentional and recorded here for phase 9b+.
  **Superseded by ADR-0022 (phase 9b):** all three machine grants are
  now guarded by the same `EnforcesProfileScopes` trait (six seams
  total); the machine deny shape is per-grant (plain league
  `invalid_scope` for client-credentials/refresh, chained
  `scope_not_permitted` for exchange).

## Exception-flow resolution (evidence note)

Early in 9a the exception path for league/Passport protocol errors was
unclear: some responses rendered in the RFC 6749 league shape even on
JSON-expecting requests, while guest bounces and middleware-direct
responses behaved differently. The mechanism is now fully resolved and
pinned:

- `Route::run()` (framework `Routing/Route.php`) catches
  `HttpResponseException` at the route-action level and returns the
  response directly — **bypassing the exception handler entirely**. Every
  league/Passport protocol error from the OAuth controllers (validate,
  approve, deny, token) goes through this catch, so the league shape
  (`{error, error_description, message}` + optional `hint`) is guaranteed
  even on `postJson` — the envelope is never involved.
- The guest `AuthenticationException` (not an `HttpResponseException`)
  propagates to the destination catch in `Routing\Pipeline`, reaches
  `Handler::render`, and the bootstrap federation callback redirects to
  the login surface — flowing back through `AppendsIssuer` untouched (no
  `code`/`error`).
- Middleware-direct responses (e.g. the PAR mandate) flow back normally
  through the issuer middleware.

## Supersession

ADR-0020's Evidence section recorded that "league access tokens carry
**no `iss`** (signature is the issuer binding; `aud` is the presenting
client)." That finding described the **access token** and remains correct
for it. Phase 9a adds an `iss` **query parameter on redirect-based
authorization responses** (D2) and an `iss` **claim on the OIDC ID token**
(D3) — a distinct surface from the league access token, which still
carries no `iss`. The BFF session decode continues to direction-check
`aud` (array-or-string tolerant); the authorization **response** is now
bound by `iss`, and the **ID token** carries `iss` as a claim.

## Consequences

- `config/federation.php` unchanged; `OAuthClientProfile` gains
  `require_par`; discovery serves `/.well-known/openid-configuration` and
  advertises the PAR endpoint (not a global mandate).
- The push endpoint (`POST /api/oauth/par`) is new surface; it is
  authenticated, rate-limited and audited like the token endpoint.
- N2 clients cannot complete an authorize flow without a pushed request;
  N1 clients are unaffected until they opt in.
- BFF callbacks fail closed on a missing or foreign `iss` before any code
  exchange.
- The A5 ceiling means a narrowed profile takes effect at the next seam a
  code crosses — a code minted before narrowing dies at the exchange.

## Evidence

- Laravel suite: **998/998 green** (pre-9a baseline 957; +41 across A1–A5
  and the 9a test batch).
- Web (jest) federation suite: **35/35 green**.
- New 9a test files: `PushedAuthorizationRequestTest` (14),
  `ParEnforcementTest` (9), `AuthorizationResponseIssuerTest` (4),
  `OidcIdTokenTest` (10), `DiscoveryMetadataTest` (4).
- Curl scoreboard (live drill, §7 pending stack relaunch):
  `docs/22-runbooks/unified-auth-curl-scoreboard.md`.
