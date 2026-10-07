# Identity & Token Platform

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 03; pasted plan principal types

The identity and token platform is the OAuth 2.0 / OIDC authorization server at the heart of Navdhya authentication: the identity model, grant matrix, token schemas, scopes, sessions/SSO/federation, service identity, and revocation/abuse controls. SCIM 2.0 provisioning lives in the ecosystem register; this file keeps the pointer.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| SEC-001 | [SEC-001](#sec-001-identity-model) | Users, principals, membership |
| SEC-002 | [SEC-002](#sec-002-authorization-server--grant-matrix) | OAuth2/OIDC server, grants |
| SEC-003 | [SEC-003](#sec-003-token-schemas--scopes) | Access/refresh/id tokens, scope model |
| SEC-004 | [SEC-004](#sec-004-sessions-sso--federation) | Step-up ACR, sessions, enterprise SSO |
| SEC-014 | [SEC-014](#sec-014-service-identity) | Machine/service principals & tokens |
| SEC-015 | [SEC-015](#sec-015-revocation-introspection--abuse-controls) | Token revocation, brute-force defense |

---

## SEC-001 Identity model

- id: SEC-001
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 03 §1
- depends_on: CON-003
- affects: SEC-002, K-001

### Identity Model

### 1.1 Entities (central connection, [tenancy & data §2.1](../06-tenancy/tenancy-and-data.md))

| Entity | Table | Purpose |
|--------|-------|---------|
| User | `users` | Global person identity (bigint PK, Passport-compatible) |
| Tenant | `tenants` | See [tenancy & data §3](../06-tenancy/tenancy-and-data.md) |
| Membership | `tenant_memberships` | User↔tenant binding with status and primary org |
| Organization | `organizations` | Tenant's org tree (`parent_id`, materialized `path`) |
| Org assignment | `org_assignments` | Additional orgs a membership can act in |
| Federated identity | `federated_identities` | Upstream IdP subject ↔ user links |
| IdP config | `identity_provider_configs` | Per-tenant upstream OIDC/SAML configuration |

### 1.2 User lifecycle (kernel state machine)

```text
invited ──(accepts invite / JIT federated login)──▶ active
active ──(admin action / SCIM deactivate)──▶ suspended ──▶ active
active / suspended ──(offboarding)──▶ deactivated (terminal, data retained per [tenancy & data §10](../13-data/data-architecture.md))
```

- `invited`: created by invite (email token, 7-day TTL, single-use).
- `active`: may authenticate; memberships still gate tenant access.
- `suspended`: all tokens/sessions revoked immediately; cannot authenticate.
- `deactivated`: terminal; login blocked; audit history preserved.

### Implementation note A-SEC-001-1 — invite administration hardened + console (2026-10-08)

**Status:** ACTIVE · **Supersedes:** nothing (gap fix + additive surface)

- `POST /api/v1/identity/invites/{id}:revoke` now enforces what the
  OpenAPI fragment always declared (required `X-Tenant-Id`) and never
  enforced: the tenant must be present (422 when absent, accepting the
  header or a `tenant_id` field), the caller needs an active membership
  in it (403 `membership_inactive`), and the invite is looked up scoped
  to that tenant — an invite outside the caller's tenant answers 404,
  the same vocabulary as a plain miss, never an existence oracle. The
  previous unscoped `find($id)` let any authenticated principal revoke
  any tenant's invite by identifier.
- New `GET /api/v1/identity/invites?tenant_id=` lists invite metadata
  for the tenant (issued/accepted/revoked state, track, identifier,
  roles, expiry) behind the same issuer-membership gate as issue —
  token material (one-time token, SHA-256 hash) never appears in the
  listing.
- Console surface `/platform/invites` (Administration → Invites): an
  issue form (track, identifier, comma roles split to `roles[]`,
  optional TTL) and a live list with confirm-gated revoke on issued
  rows. One-action rule (doc 06 §7): the forms post the SHARED
  actions, no step-up on either side — invites carry no step-up on the
  API, and the actions check tenant + issuer membership themselves.
  The one-time acceptance token rides the success flash, consumed by
  that single render; API envelopes stay byte-identical (`InviteTest`
  + the full suite are the parity pin). Verified by
  `AdminInvitesSurfaceTest`.

### 1.3 Key schema decisions

`users`: `id BIGINT UNSIGNED PK`, `name`, `email UNIQUE`, `email_verified_at`,
`password` (nullable — federated-only users have none), `status`,
`mfa_enabled`, `locale`, `last_login_at`, timestamps, soft delete.
Password hashing: Argon2id (Laravel default).

`tenant_memberships`: `id ULID`, `user_id`, `tenant_id`, `status`
(`active, suspended, revoked`), `primary_org_id`, `invited_by`,
`joined_at`, `last_active_at`; `UNIQUE (tenant_id, user_id)`.

`organizations`: `id ULID`, `tenant_id`, `parent_id NULL`, `name`,
`path` (e.g. `/root/region-west/branch-01`), `depth`, `status`.

`federated_identities`: `id ULID`, `user_id`, `idp_config_id`,
`subject` (upstream `sub` / SAML NameID), `email`, `claims_snapshot JSON`,
`linked_at`; `UNIQUE (idp_config_id, subject)`.

### 1.4 Multi-factor authentication (kernel-native)

- TOTP (RFC 6238) with 30 s step, ±1 window; enrollment requires confirming
  one code; 10 single-use recovery codes issued at enrollment (hashed at rest).
- MFA state is recorded on `users.mfa_enabled` and in the session/token
  `amr` claim (`pwd`, `otp`, `mfa`, `sso`).
- Tenants MAY enforce MFA for all members (`tenants.require_mfa` flag):
  enforced users without MFA are pushed to enrollment at next login and
  receive ACR 1 until enrolled.

### Implementation note A-SEC-004-1 — TOTP live (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- `POST /api/v1/identity/mfa/enroll|confirm|verify|revoke` +
  `GET /api/v1/identity/mfa` (user-level, no tenant scope; machines
  denied): secrets application-encrypted, enabled only after a
  confirming code, recovery codes single-use hashed (shown once),
  revocation needs a live TOTP code (recovery codes authenticate but
  never revoke), code failures collapse to one 401, code endpoints
  throttled. Lifecycle emits `platform.mfa.enrolled/revoked` (no
  audit rows — identity self-service precedent, no tenant context).
- Session/token `amr` claims and tenant `require_mfa` enforcement
  still pending. `/oauth/stepup` landed — see A-SEC-004-3.

---

## SEC-002 Authorization server & grant matrix

- id: SEC-002
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 03 §2–3
- depends_on: SEC-001
- affects: SEC-003

### Authorization Server

### 2.1 Stack decision (normative)

- **Laravel Passport** provides the OAuth 2.0 core: authorization endpoint,
  token endpoint, keys, personal/client tokens.
- The **kernel OIDC layer** (built on Passport, not replacing it) adds:
  ID token issuance, `/.well-known/openid-configuration`,
  `/.well-known/jwks.json`, `/oauth/userinfo`, and the step-up/ACR machinery.
- **No custom crypto**: RS256 keypair managed by Passport (`passport:keys`),
  keys stored with restrictive permissions, private key never leaves the
  server ([security baseline §3](./security-baseline.md)).
- The **resource-owner password grant is BANNED** (Passport password grant
  MUST NOT be enabled).

### 2.2 Endpoint inventory

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/.well-known/openid-configuration` | GET | OIDC discovery (public) |
| `/.well-known/jwks.json` | GET | Public signing keys (public, cacheable) |
| `/oauth/authorize` | GET/POST | Authorization code + PKCE consent |
| `/oauth/token` | POST | Token issuance (all grants) |
| `/oauth/revoke` | POST | Token/grant revocation (RFC 7009) |
| `/oauth/introspect` | POST | RFC 7662 introspection (restricted audience) |
| `/oauth/userinfo` | GET/POST | OIDC userinfo |
| `/oauth/device/code` | POST | Device authorization request (RFC 8628) |
| `/oauth/device` (verification UI) | GET/POST | User enters `user_code` (web, session-auth) |
| `/oauth/stepup` | GET/POST | Step-up re-authentication (MFA / fresh login) |
| `/api/v1/identity/mfa/*` | POST | TOTP enrollment/verify/recovery |
| `/api/v1/identity/sessions` | GET/DELETE | List / revoke own web sessions |

All token endpoint traffic MUST be TLS-only; CORS is configured per
registered client origin only.

### Implementation note A-SEC-002-1 — discovery + userinfo live (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- `/.well-known/oauth-authorization-server` (RFC 8414, throttled,
  cacheable) instead of `openid-configuration`: OIDC discovery implies
  `id_token` issuance, which is not built — the RFC 8414 document
  carries everything an OAuth client needs today. OIDC discovery lands
  with `id_token`s.
- `/.well-known/jwks.json` publishes the active RSA key (`kty/use/alg/
  n/e`, cacheable). No `kid` correlation: league mints kid-less
  tokens; rotation dual-publishes when the time comes.
- `GET /oauth/userinfo` (any accepted credential, no tenant scope —
  identity-level): `{sub, name, email(+verified), phone(+verified)}`
  with `sub` = external_id, falling back to the user id.
- Still pending: `id_token` issuance, OIDC discovery, introspection
  (§8.2), device flow, step-up, MFA endpoints.

### 2.3 Client registry

Passport `oauth_clients` is extended by kernel table `oauth_client_profiles`:

| Column | Notes |
|--------|-------|
| `client_id` | FK to oauth_clients.id |
| `client_type` | `first_party_web | first_party_service | partner | pos_device` |
| `tenant_id` | NULL for first-party; REQUIRED for partner/pos clients |
| `allowed_grants` | JSON subset of `authorization_code, client_credentials, refresh_token, urn:ietf:params:oauth:grant-type:device_code` |
| `allowed_scopes` | JSON; empty = none (least privilege default) |
| `audience` | URI this client's tokens are valid for (e.g. `https://api.navdhya.example/v1`) |
| `access_ttl_seconds` / `refresh_ttl_seconds` | Per-client overrides within kernel maxima |
| `require_pkce` | Default true for code-grant clients |
| `rate_tier` | Consumed by throttling ([API platform §6](../07-kernel/api-platform.md)) |
| `redirect_uris` | Registered redirect URIs (exact match, no wildcards) |

Seeded first-party clients (Phase 0): **Blade admin web client**
(auth code + PKCE, confidential, session-bound) and **internal service
client** (client credentials, platform-global, non-sensitive scopes only).
Partner and POS clients are created per tenant via
`platform:client:create` permission (sensitive).

---

### Grant Matrix (Normative)

| Grant | Use case | PKCE | Consent | Step-up capable |
|-------|----------|------|---------|-----------------|
| Authorization Code + PKCE | Blade admin, future SPA, mobile | REQUIRED (S256) | First-party: implicit; partner: explicit consent screen | yes |
| Refresh Token (rotation) | All interactive clients | n/a | inherited | n/a |
| Client Credentials | Server-to-server, partners, internal services | n/a | none (pre-authorized scopes) | **no — sensitive scopes MUST NOT be granted** |
| Device Code (RFC 8628) | POS terminals, headless devices | n/a | user approves on web | yes (at approval) |
| Resource Owner Password | — | — | — | **BANNED, MUST NOT be enabled** |
| Implicit | — | — | — | **BANNED** |

### 3.1 Authorization Code + PKCE

1. Client redirects to `/oauth/authorize` with `response_type=code`,
   `client_id`, `redirect_uri` (exact-registered), `scope`, `state`,
   `code_challenge` (S256, REQUIRED), `code_challenge_method=S256`,
   `nonce` (OIDC clients), optional `acr_values`, optional `org_id`.
2. User authenticates (session or federated SSO); consent shown for partner
   clients listing requested scopes in human-readable form.
3. Auth code: single-use, 60 s TTL, bound to `code_challenge` and client.
4. Token exchange validates `code_verifier` against the stored challenge.
5. Response: `access_token` (JWT), `refresh_token` (opaque, rotating),
   `id_token` (OIDC clients), `expires_in`, `scope`, `token_type=Bearer`.

Requested scopes beyond the user's granted permissions are **down-scoped
silently to the intersection** and reflected in the returned `scope`
(effective authority rule, [authorization & policy §5](./authorization-and-policy.md)).

### 3.2 Refresh rotation with reuse detection

- Every refresh token belongs to a **lineage** (`lineage_id` on
  `oauth_refresh_tokens` extension column).
- On refresh: a **new refresh token** is issued and the presented one is
  atomically revoked (`revoked=1`) in the same transaction.
- **Reuse detection:** if a request presents an already-revoked refresh
  token, the kernel MUST revoke the **entire lineage** (all refresh + access
  tokens via `jti` revocation), kill the user's sessions for that client,
  emit `platform.token.reuse_detected` (security event → alert), and return
  `invalid_grant`.
- Refresh TTL: 30 days absolute per lineage; sliding renewal on each use;
  absolute lifetime never extends beyond 30 days without fresh interactive
  login.

### 3.3 Client Credentials

- Client authenticates with `client_id` + `client_secret` (HTTP Basic
  preferred); tokens carry the client as subject (`sub = client:<id>`).
- Issued scopes = requested ∩ `allowed_scopes` from the client profile.
- Partner clients are bound to exactly one tenant → token carries singular
  `tenant_id` claim; no user context, no sensitive permissions, no step-up.
- Secrets: 256-bit random, hashed at rest (Passport behavior), shown once at
  creation; rotation supported (`client:rotate-secret`, sensitive permission).

### 3.4 Device Code (RFC 8628) for POS

1. POS device POSTs `/oauth/device/code` (client auth only) → receives
   `device_code`, `user_code` (8 chars, human-friendly charset),
   `verification_uri`, `verification_uri_complete` (with user_code),
   `expires_in=600`, `interval=5`.
2. Device displays `verification_uri` + `user_code`; operator signs in on
   any web browser (session auth, MFA if enforced) and approves the device
   with its requested scopes.
3. Device polls `/oauth/token` with
   `grant_type=urn:ietf:params:oauth:grant-type:device_code`:
   `authorization_pending` / `slow_down` (interval +5 s on fast poll) /
   `expired_token` / `access_denied` handled per RFC.
4. On approval, tokens are issued like the code grant; the POS device stores
   the rotating refresh token securely (OS keychain/encrypted store).

Device clients are per-tenant, per-terminal records
(`client_type=pos_device`) so a compromised terminal can be revoked
individually without affecting the store.

### Implementation note A-SEC-002-1 — device flow live (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- Stock league device grant (already enabled server-wide) + two
  standalone verification views (`oauth.device.user-code` entry,
  `oauth.device.authorize` approve/deny with `auth_token` binding) +
  `pos_device` client type in `platform:client:create` (tenant
  REQUIRED, one row per terminal; grants device-code + refresh).
- Verified paths: pending → slow_down on fast poll → web approval →
  tokens on poll; denial → `access_denied` on poll. Device-code
  redemption passes the same per-client grant middleware as all token
  grants.
- Still pending: device-client revocation console (per-terminal kill
  is a client row revocation today), `expired_token` path coverage.

---

## SEC-003 Token schemas & scopes

- id: SEC-003
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 03 §4–5
- depends_on: SEC-002
- affects: SEC-010

### Token Schemas

### 4.1 Access token (JWT, RS256)

TTL: **15 minutes** (kernel maximum; per-client overrides may only lower).

| Claim | Presence | Meaning |
|-------|----------|---------|
| `iss` | always | Platform issuer URL |
| `sub` | always | User id, or `client:<id>` for client credentials |
| `aud` | always | Audience URI of the issuing client profile; resource servers MUST reject mismatched audiences |
| `exp`, `iat`, `nbf` | always | Standard |
| `jti` | always | Unique id; checked against `jti_revocations` |
| `client_id` | always | OAuth client that obtained the token |
| `scope` | always | Space-delimited effective scopes |
| `tenant_id` | CC tokens | Singular tenant binding |
| `tenant_ids` | user tokens | All tenant ids with an active membership at issuance |
| `org_id` | optional | Present when authorization fixed an org (pre-bound clients or `org_id` authorize param); otherwise per-request resolution ([tenancy & data §4.3](../06-tenancy/tenancy-and-data.md), `X-Org-Id`) |
| `acr` | user tokens | `1` or `2` (see §6) |
| `amr` | user tokens | Array: `pwd`, `otp`, `mfa`, `sso` |
| `auth_time` | user tokens | Unix time of the underlying authentication |
| `sid` | user tokens | SSO session id (for logout propagation) |

**Tenant set freshness:** `tenant_ids` is re-validated at every request
against live membership status (claim is a convenience, not authority), so
membership revocation takes effect immediately despite token TTL.

### 4.2 ID token (OIDC)

Standard claims (`iss, sub, aud, exp, iat, auth_time, nonce, acr, amr, sid`)
plus profile claims (`name, email, email_verified`). Issued at authorize and
step-up; MUST NOT be accepted as an API access token.

### 4.3 Refresh token

Opaque 256-bit string, hashed at rest; fields: `lineage_id`, `client_id`,
`user_id`, `tenant_scope JSON`, `scopes`, `revoked`, `expires_at`.

### 4.4 Key management

- RS256 keypair via `passport:keys`; key rotation runbook: generate new key,
  JWKS publishes **both** keys during a 7-day overlap, old key retired
  ([security baseline §3](./security-baseline.md)). `kid` header present on all JWTs.
- JWKS response carries `Cache-Control: public, max-age=3600`.

---

### Scopes

Naming (mirrors permission codes, [authorization & policy §6](./authorization-and-policy.md)):

- `<module>:read`, `<module>:write` — coarse scopes auto-expanded by the
  kernel to the module's view/list and mutating permission sets.
- `<module>:<resource>:<action>` — fine-grained scope for specific verbs
  (declared in the module manifest `scopes`).
- Kernel scopes: `kernel:profile:read`, `kernel:mfa:manage`,
  `kernel:sessions:manage`, `kernel:tenant:list`, `kernel:scim:write`,
  `kernel:webhooks:manage`, `kernel:hooks:manage`, `kernel:notifications:send`.

Scope rules:
1. Scope requests are capped at the **client profile's allowed_scopes** and
   again at the **user's permission grants** (double intersection).
2. Client-credentials clients MUST NOT be granted any permission marked
   `sensitive: true` (architecture test enforces).
3. Unknown scopes → `invalid_scope` error.

---

## SEC-004 Sessions, SSO & federation

- id: SEC-004
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 03 §6, §7, §9
- depends_on: SEC-002
- affects: EXP-014

### Step-Up Authentication (ACR)

ACR levels:

| ACR | Meaning | How reached |
|-----|---------|-------------|
| `0` | unauthenticated | — |
| `1` | single-factor (password or federated SSO without MFA assertion) | normal login |
| `2` | multi-factor or fresh re-authentication | password+TOTP, federated MFA, or successful step-up within TTL |

Normative behavior:
1. Module manifests mark operations `sensitive: true` (e.g.
   `sales:order:approve`, role grants, secret rotation, tenant promotion,
   payout/bank-detail changes).
2. Routes for sensitive operations carry the `requires_acr:2` middleware.
3. Web: if session ACR < 2 or `auth_time` older than **15 minutes**, the
   user is redirected to `/oauth/stepup` (password and/or TOTP), then
   returned to the original action. Session ACR is upgraded on success.
4. API: request receives `403` with error envelope code `step_up_required`
   and a `details.stepup_url` pointing at the authorize flow with
   `acr_values=2&prompt=login`; interactive clients re-authorize and retry.
5. Client-credentials tokens can never satisfy ACR 2; sensitive scopes are
   simply unavailable to them (§5.2).

### Implementation note A-SEC-004-2 — step-up gate live (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- `stepup:2` middleware on API key issue/rotate/revoke + role grants:
  fresh MFA proof (step-up row < 15 min) or 403 `step_up_required`
  (unenrolled vs stale distinguished by message; machines always
  denied). Every MFA verify writes a proof row; rows sweep after 24 h.
- Deltas vs the normative text above: the middleware alias is `stepup`
  (not `requires_acr`); denials carry actionable messages instead of
  `details.stepup_url` (no step-up UI flow exists yet); v1 has no
  password re-auth alternative — unenrolled users cannot perform
  sensitive operations, by fail-closed design.
- Still pending: web redirect flow (`/oauth/stepup`), session ACR
  tracking, `amr` claims, tenant `require_mfa` enforcement, and
  further sensitive-route rollout.
- Rollout (2026-10-02): onboarding review approve/reject decisions
  (request-info returns work to draft — not sensitive, ungated);
  user suspend/activate (credential kill/restore); role assignment
  revoke, symmetric with grant.
- Deliberately ungated: MFA revoke + recovery-code regeneration demand
  a live TOTP code (fresher than any 15-minute proof —
  step-up-equivalent by construction); session revoke-others stays
  available to unenrolled users (self-defense against stolen sessions
  must not require enrollment).
6. Every step-up emits `platform.session.stepup_completed` (audit).

### Implementation note A-SEC-004-3 — web step-up flow live (2026-10-08)

**Status:** ACTIVE · **Supersedes:** A-SEC-004-2's "web redirect flow
pending" delta (additive)

- `/oauth/stepup` (web, session-auth): GET renders the TOTP/recovery
  form — or, fail-closed, the enrollment-required notice — and answers
  the console's freshness probe as JSON (`Accept: application/json` →
  `{mfa_enabled, fresh}`). POST shares `MfaController::verify` (doc 06
  §7 one-action rule): the proof row it writes is exactly what
  `stepup:2` reads, so form and API verify through one code path.
  Success redirects to `return_to`, clamped to same-site paths
  (off-site, protocol-relative or backslash targets collapse to `/`).
- Console flow (doc 06 §7.5): sensitive forms confirm, then a shared
  gate in `admin-api.js` probes freshness — a fresh proof posts
  straight away; otherwise a modal collects the code, posts
  `/oauth/stepup`, and re-probes before the action goes out (a
  followed redirect can never masquerade as success). Without MFA or a
  verified code the form never posts; the server gate answers 403
  `step_up_required` regardless.
- First consumers: user suspend/activate (credential kill/restore),
  the users console's role grant/revoke panel, and the API keys page
  (issue/rotate flashes carry the one-time secret, escaped by the
  view) — web routes mirror the API guards exactly (`tenant` +
  `platform:user:manage` + `stepup:2` for lifecycle; `stepup:2` alone
  for grants and key administration, which carry their own
  membership/SoD checks) and negotiate through `WebMutations` (web →
  redirect + coded flash, API → byte-identical envelope). Every
  step-up write now has a console surface.
- Still pending from A-SEC-004-2: session ACR tracking, `amr` claims,
  and tenant `require_mfa` enforcement.

### Implementation note A-SEC-004-4 — security settings console page (2026-10-08)

**Status:** ACTIVE · **Supersedes:** nothing (additive; completes
A-SEC-004-3's fail-closed notice destination)

- `/account/security` (web, session-auth): the console's security page —
  profile name edit, TOTP enrollment/confirmation/management, and the
  web session inventory with revoke-others. Served by the admin shell
  catch-all, reachable from the topbar dropdown; the step-up form and
  modal notices now link here, so the enrollment nudge is no longer a
  dead end.
- One-action rule holds throughout (doc 06 §7): reads travel as
  session-cookie GETs (`/api/v1/identity/mfa`, `/identity/sessions`;
  the profile form is prefilled server-side), writes are CSRF forms
  posting the SHARED actions — `/account/security/...` mirrors the
  API's `auth.any` + session guards with the web `auth` group,
  negotiated by `WebMutations`. No step-up gate anywhere in the group:
  enrolling MFA must work before MFA exists, and revoke-others stays
  available to unenrolled users (A-SEC-004-2 self-defense precedent).
  The API stays bearer/CSRF-exempt — cookie-authenticated writes never
  post to `/api/v1`.
- One-time values ride the coded flash, consumed by that single render:
  the enrollment setup key (extracted from the `otpauth` URL), the
  recovery codes on confirm/regenerate, and the revocation count.
  `updateProfile`'s strict unknown-field check ignores `_token` only —
  web CSRF bookkeeping, never a client field.
- Verified by `SecurityConsoleTest`; `MfaTest` and the identity
  self-service tests remain the API parity pin (envelopes stay
  byte-identical through the widening).

---

### Sessions & SSO Semantics

- **Web session**: Laravel session (Redis in prod), idle timeout 8 h,
  absolute 24 h; session bound to user agent + IP bucket — drift forces
  re-authentication. Active sessions are listed/revocable at
  `/api/v1/identity/sessions`.
- **SSO session**: the platform session created after federated login carries
  `sid`; tokens issued from it embed the same `sid` so revocation can fan
  out.
- **Single logout direction (v1):**
  - *Platform-initiated*: logging out destroys the platform session and adds
    the session's token lineage to revocation; if the upstream IdP supports
    RP-initiated logout (OIDC `end_session_endpoint` / SAML SLO), the
    platform redirects/posts the logout there (front-channel, best effort).
  - *IdP-initiated*: where the upstream supports front-channel logout, the
    platform exposes `/sso/logout-callback` and destroys the matching
    session via `sid`/subject lookup. Back-channel SLO is out of scope for v1.

---

### Federation (Enterprise SSO)

### 9.1 IdP configuration (per tenant)

`identity_provider_configs` fields: `id ULID`, `tenant_id`, `protocol`
(`oidc|saml`), `name`, `email_domains JSON` (routing domains),
`enforced` (bool — password login disabled for matched domains),
`jit_enabled`, `jit_default_role`, `jit_default_org_id`,
`oidc_settings JSON` (issuer, client_id, secret ciphertext, scopes,
claim_map), `saml_settings JSON` (entityId, SSO url, certificate, NameID
format, claim_map), `scim_enabled`, `scim_group_map JSON`, `status`.

### 9.2 Discovery & routing (email-domain)

1. User submits email at login → kernel queries the domain index built from
   all active IdP configs.
2. Exactly one tenant+IdP match with `enforced=true` → password form is
   replaced by immediate redirect to the upstream IdP (no local password
   accepted for that domain).
3. Multiple matches (rare: same domain across tenants) → tenant chooser.
4. No match → normal password login (subject to user existence and status).
5. Direct deep links `/sso/{tenantSlug}` skip email discovery.

### 9.3 OIDC relying party

- Authorization Code flow to the upstream IdP with `state` + `nonce`,
  PKCE where supported; `max_age` honored when the platform demands fresh
  auth.
- `id_token` validation: signature (upstream JWKS), iss, aud, exp, nonce.
- Claims mapped via `claim_map` (e.g. `{"email":"mail","name":"displayName","org":"department"}`).

### 9.4 SAML 2.0 service provider

- Implemented with a maintained SAML SP library (e.g. `onelogin/php-saml`);
  no hand-rolled XML signing.
- SP metadata published per tenant at `/sso/{tenantSlug}/saml/metadata`;
  ACS at `/sso/{tenantSlug}/saml/acs` (POST binding).
- Responses validated: signature against configured certificate, audience,
  `NotBefore/NotOnOrAfter`, ReplayGuard via assertion ID cache (10 min).
- NameID persistent format preferred; email fallback with verification.

### 9.5 JIT provisioning & account linking

On successful federated assertion:
1. Look up `federated_identities` by `(idp_config_id, subject)` → known user.
2. Else if `jit_enabled`: create user (`status=active`,
   `email_verified_at=now` when asserted by IdP, no password), create
   membership in the IdP's tenant with `jit_default_role` +
   `jit_default_org_id`, record the federated identity — emit
   `platform.user.provisioned`.
3. Else: deny with `sso_not_provisioned` (contact-admin message).
4. Known user with no active membership in this tenant → deny
   (`membership_missing`) — federation never resurrects revoked access.

Linking policy: a federated identity links to at most one user; linking an
additional external identity to an existing user requires ACR 2 and the
`kernel:profile:manage` scope.

---

## SEC-014 Service identity

- id: SEC-014
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 03 §1/§4; pasted plan principal types
- depends_on: SEC-001, K-001
- affects: SEC-024, 17-ai

Service identity covers non-human principals: `service`, `agent`, and `partner` principals authenticate with machine tokens (client-credentials) and are modeled by the Principal contract (K-001). Machine tokens MUST NOT carry `sensitive`-flagged scopes; agent authorization is governed by the AI-agent safety rules (SEC-024). Service principals are tenant-scoped unless they are platform-level kernel services.

### Amendment A-SEC-014-1 — machine API keys (interim, pre-Passport)

**Status:** ACTIVE · **Date:** 2026-10-01 · **Supersedes:** nothing (additive)

Until TOKEN-001 (Passport JWT) lands, server-to-server authentication uses
tenant-bound machine API keys (`api_keys`, central connection) alongside
the frontend HMAC dual-accept bridge:

- **Issuance:** `POST /api/v1/platform/api-keys` (human session + active
  membership required — machine principals get explicit 403). The
  plaintext secret (`sk_live_…`) is returned **once**; only its SHA-256
  persists (`secret_hash`). Listing (`GET …/api-keys`) returns metadata
  only — never secrets or hashes.
- **Authentication:** `auth.any` middleware (single entry point: session →
  HMAC bearer → API key; never stacked `auth` aliases). The key's 15-char
  prefix locates the row; `hash_equals(secret_hash, sha256(presented))`
  verifies. Success installs a `MachinePrincipal` (key tenant + scopes,
  never a `User` impersonation) and stamps `last_used_at` best-effort.
- **Tenant binding:** `ResolveTenantContext` accepts a machine principal
  only when the key's tenant equals the `X-Tenant-Id` header tenant —
  cross-tenant keys fail closed (401). Key principals carry no membership,
  so the policy engine's allow-grant stage (membership-gated) default-denies
  them: capability-gated writes return 403.
- **Lifecycle:** revoke is immediate (`revoked_at`); rotation mints a
  replacement row and caps the old key with a 24 h overlap expiry (existing
  expiry always wins) — never secret edits. Unknown prefixes,
  revoked/expired keys, and garbage bearers all deny with identical 401s.
- **Deferred to Passport/Redis:** `rate_tier`
  is stored (`standard|professional|enterprise|partner`) and enforced only
  by the DB-backed ceilings until REDIS-001 unblocks tiered throttling.

### Amendment A-SEC-014-2 — machine scope enforcement (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- Ownership reads consumable by machines declare `scope:<scope>`
  middleware (`CheckScope`, alias `scope`): catalog item reads
  (`catalog:read`), reservation reads + history (`booking:read`).
- Humans pass through (grants govern); machines need exact scope match
  (no wildcards, no write-implies-read v1) or 403 `insufficient_scope`
  (kernel registry code, unaudited like credential errors). Capability
  routes still default-deny machines — scopes never substitute grants.
- Key issuance validates scope shape (`module:verb`); rollout rule: every
  new ownership-read added for machine consumption declares its scope.

### Amendment A-SEC-014-3 — tiered machine throttling (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- `throttle.tier` middleware on the machine-read surface (catalog +
  reservation reads, after `scope` so denials burn no quota): per-key
  per-minute ceilings from the key tier through the driver-agnostic
  limiter — standard 60, professional 300, enterprise 1200, partner 600
  (unknown tiers fall back to standard). Humans pass through; excess
  throws the framework throttle exception → 429 `rate_limited` with
  `Retry-After: 60` (already the envelope mapping).
- Single-instance ceilings by REDIS-001 design (array/database cache);
  shared/distributed throttling waits on Redis like everything else.

OpenAPI: `app/Platform/Identity/Http/openapi-keys.yaml` (composed into
`docs/api/openapi.v1.yaml`); tests: `tests/Platform/Identity/ApiKeyTest.php`.

### Amendment A-SEC-014-4 — hybrid HMAC/JWT forever (TOKEN-001 P4b, 2026-10-03)

**Status:** ACTIVE · **Supersedes:** "P4b HMAC sunset" language in TOKEN-001
blocker (additive)

- **Decision:** HMAC actor tokens are **permanent** for machine/BFF
  surfaces (service identity, customer BFF login); Passport JWT
  **only** for interactive user tokens (OAuth/OIDC flows).
  Dual-accept in `auth.any` stays by design — no sunset date, no
  flag-day migration.
- **Authority:** tenant authority stays on live membership (P3b
  deliberate fail-closed). Custom JWT claims (`tenant`, `acr/sid`)
  are optimization, not authority; tracked as optional S6b.
- **Impact:** blocker TOKEN-001 marked resolved; remaining tracks
  limited to optional JWT claim enrichment. HMAC `ActorTokenIssuer`
  and Passport dual-accept both ship; `config/customers.php`
  `OTP_LOG_CODES` drill flag unchanged.

## SEC-015 Revocation, introspection & abuse controls

- id: SEC-015
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 03 §8, §11
- depends_on: SEC-003
- affects: SEC-016

### Revocation & Introspection

### 8.1 Revocation

- `jti_revocations` (central): `jti`, `reason`
  (`user_logout, reuse_detected, membership_revoked, admin_revoke, key_rotation`),
  `revoked_at`, `expires_at` (= original token exp; rows auto-purged).
- `/oauth/revoke` implements RFC 7009 for refresh tokens and access tokens
  (by `jti`).
- Bulk revocation triggers: user suspension/deactivation (all tokens, all
  sessions), membership revocation (tokens' tenant set re-check handles this
  live; refresh lineages for that tenant revoked), tenant suspension, client
  deletion/secret rotation.
- Resource-server check order per request: signature → exp/nbf → aud →
  `jti` revocation → live membership/scope resolution. Revocation lookup is
  Redis-cached (TTL 30 s) with write-through on revoke.

### Implementation note A-SEC-015-1 — revocation endpoint live (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- `POST /api/oauth/revoke` (client-authenticated, throttled, public):
  access-token revocation cascades to its refresh tokens (else refresh
  resurrects the session); refresh revocation symmetrically kills its
  access token. Refresh tokens travel league-encrypted — the endpoint
  decrypts to the stored id first; undecryptable input is an unknown
  token (silent 200, no oracle). Errors use league's shape, not the
  kernel envelope (protocol surface). Dual-accept honors revocation
  immediately (proven end to end).
- `jti_revocations` table, Redis revocation cache, and back-channel SLO
  remain future work; revocation state today is the `revoked` flags on
  the Passport tables.

### 8.2 Introspection (RFC 7662)

`/oauth/introspect` accepts a client-authenticated request and returns the
active token's claims (`active, scope, client_id, sub, tenant_id(s), org_id,
acr, exp`). Audience: **restricted to registered first-party service
clients** — this is the mechanism future extracted services use to validate
tokens without shared key access patterns changing (carve-out rule C4,
[domain framework §6](../02-architecture/architecture-master.md)). Rate-limited per client; every introspection call is logged.

### Implementation note A-SEC-015-2 — introspection live (2026-10-02)

**Status:** ACTIVE · **Supersedes:** nothing (additive)

- `POST /api/oauth/introspect` (client-authenticated, throttled):
  first-party service clients only (partners get 403); access tokens
  resolve claims from the row + JWT scopes, refresh tokens resolve
  client/exp; unknown/expired/revoked answer `{active: false}` without
  distinguishing. Every call is operationally logged (no tenant
  context exists at this layer, so kernel audit rows don't apply).
- No `tenant_id(s)`/`acr` claims yet (tokens don't carry them — custom
  claims land with P4b); no Redis cache (REDIS-001) — lookups are
  indexed DB reads.

---

### Brute-Force & Abuse Controls

- Login: per-email exponential throttle (5 failures → 15 min lockout on
  password attempts for that account) **and** per-IP rate limit; failed
  attempts logged with request id.
- `/oauth/token`, `/oauth/device/code`: aggressive per-client + per-IP
  rate tiers ([API platform §6](../07-kernel/api-platform.md)).
- Invite and password-reset tokens: 32-byte random, hashed at rest, 24 h /
  7-day TTLs respectively, single-use, bound to the target email.
- CAPTCHA-free by design in v1; abuse signals feed the cataloged security
  events (notably `platform.token.reuse_detected`, §12) plus audit entries for
  failed logins (no per-failure event).

---

---

## Supplementary migrated content

### SCIM 2.0 provisioning (migrated to ecosystem)

SCIM 2.0 provisioning is specified in the [ecosystem register](../18-extensions/ecosystem.md), which retains the full section ([ecosystem §6](../18-extensions/ecosystem.md) SCIM GA). This identity document keeps only this pointer. SCIM GA deltas over the v1 SCIM surface are recorded there.

---

### Identity event catalog (kernel namespace `platform`)

| Event | Trigger |
|-------|---------|
| `platform.user.invited` | Invite issued |
| `platform.user.created` | Account created (password path) |
| `platform.user.provisioned` | JIT/SCIM provisioning created a user |
| `platform.user.updated` | Profile/status change |
| `platform.user.suspended` / `platform.user.deactivated` | Lifecycle transitions |
| `platform.membership.created` / `platform.membership.activated` / `platform.membership.revoked` | Membership lifecycle |
| `platform.role.assigned` / `platform.role.revoked` | Role assignment changes ([authorization & policy](./authorization-and-policy.md)) |
| `platform.mfa.enrolled` / `platform.mfa.revoked` | MFA lifecycle |
| `platform.session.stepup_completed` | Step-up succeeded |
| `platform.session.revoked` | Session destroyed by user/admin |
| `platform.token.revoked` | Token/grant revocation |
| `platform.token.reuse_detected` | Refresh reuse → lineage revoked (security alert) |
| `platform.idp.configured` / `platform.idp.updated` | Federation config changes |
| `platform.scim.user_provisioned` / `platform.scim.user_updated` / `platform.scim.user_deactivated` / `platform.scim.group_synced` / `platform.scim.drift_detected` | SCIM operations |
| `platform.client.created` / `platform.client.secret_rotated` / `platform.client.revoked` | OAuth client lifecycle |

---

---

### Verification requirements (feed into testing strategy)

Token-flow E2E scenarios that MUST pass:
1. Code+PKCE happy path incl. consent down-scoping.
2. PKCE verifier mismatch rejected.
3. Refresh rotation: old refresh rejected after use; reuse revokes lineage
   and kills sessions; `platform.token.reuse_detected` emitted.
4. Client credentials: scope capping, tenant binding, sensitive-scope denial.
5. Device flow: pending/slow_down/expired/approved paths.
6. Step-up: sensitive route blocked at ACR 1, passes after step-up; API
   returns `step_up_required` envelope.
7. Revocation: revoked `jti` rejected within 30 s cache bound; membership
   revocation immediately denies despite valid token.
8. Federation: enforced-domain password login blocked; JIT creates
   user+membership; revoked membership cannot re-enter via SSO.
9. SCIM: provisioning, deactivation (token revocation), group→role mapping,
   error envelope conformance.

---

---

### Assumptions

- Passport remains the OAuth core; if a future need exceeds Passport
  (e.g. FAPI), the kernel OIDC layer is the documented swap point — endpoint
  contracts in this doc are stable regardless.
- One platform issuer URL per environment; tenants are not custom-domain
  issuers in v1.
- Upstream IdPs are enterprise-grade (Entra ID, Google Workspace, Okta);
  consumer social login (sign-in with Google-as-consumer) is out of scope.
