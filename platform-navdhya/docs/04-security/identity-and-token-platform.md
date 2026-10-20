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
| `/oauth/logout` | GET/POST | RP-initiated end-session + logout fan-out (A-SEC-004-5) |
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
  ([security baseline §3](./security-baseline.md)). `kid` header on ID
  tokens (RFC 7638 thumbprint); league's access-token headers stay
  kid-less (A-SEC-002-1) — consumers fall back to the small JWKS set.
- Signing-key custody is pluggable and fail-closed — `AUTH_SIGNING_CUSTODY`
  = `file` (default) or `kms` (KMS/HSM; the private half never exists in
  process memory); see A-SEC-003-1.
- JWKS response carries `Cache-Control: public, max-age=3600`.

### Implementation note A-SEC-003-1 — signing-key custody: file + KMS/HSM, live (2026-10-16)

**Status:** ACTIVE · **Supersedes:** nothing (additive; extends §4.4)

Custody is a security boundary, not a default. `AUTH_SIGNING_CUSTODY`
resolves the token-signing key source behind one port
(`Signing/SigningKeyProvider`: `assertUsable()` fail-closed boot check,
`publicKeyPem()` the JWKS source, `jwtSigner()` the mint signer — RS256
in every custody). Unknown values refuse the boot.

- **`file` (default):** inline `PASSPORT_PRIVATE_KEY` or the
  `oauth-private.key` pair at Passport's key path — exact pre-custody
  availability semantics, byte-exact signed output (rotation drill +
  OIDC suites pin it).
- **`kms`:** external KMS/HSM. Config: `AUTH_SIGNING_KMS_KEY_RESOURCE`,
  `AUTH_SIGNING_KMS_ENDPOINT`, `AUTH_SIGNING_KMS_PUBLIC_KEY` (+ optional
  `AUTH_SIGNING_KMS_AUTH_TOKEN`, `AUTH_SIGNING_KMS_TIMEOUT`). The
  private-half operation rides the `SignsTokenRemotely` port;
  `KmsHttpSigner` speaks the shim contract — POST
  `{algorithm: "RS256", signing_input: <base64url>}` →
  `{signature: <base64url(DER)>}`, exactly cloud KMS `asymmetricSign`
  output, so the shim is a thin transcoder with no key material. The
  issuer boots under this custody only when the config validates AND
  the port is bound — otherwise the boot is refused: a JWKS-published
  key nothing can sign under is the "publish one key, sign with
  another" failure.
- **Mint-path interception (complete by construction):** league emits
  `$accessToken->toString()`, and `CustodyAccessToken`
  (`Passport::useAccessTokenEntity`) overrides exactly that one method —
  no grant (auth code, refresh, client credentials, token exchange) can
  mint around it. File custody delegates to league's stock path;
  remote custody mirrors league 9.4.1's claim layout body-for-body and
  signs through the port. The ID-token grant takes its signer from the
  same custody. JWKS (`DiscoveryController::publicKey`) and the
  ID-token `kid` resolve through the same provider — in sync by
  construction, no per-call file walk to keep aligned.
- **Sentinel materialization:** under `kms`, Passport's key slots are
  filled from the custody's public half — the verification side reads
  it, and the private slot holds the SAME public PEM (league's
  `CryptKey` validates form only, and a public PEM passes), so any
  signing path that slips past interception fails LOUDLY
  (`openssl_sign` rejects a public key) instead of signing under a key
  consumers don't have. No real private half exists in process memory.
- **Rotation with overlap:** file custody stays on
  `auth:rotate-signing-key`. External swaps (KMS key versions,
  deployment-managed PEMs) run `auth:retire-signing-key` BEFORE the
  swap: the current public half is archived to
  `retired_signing_keys_dir/<kid>.pub` (JWKS keeps serving it),
  `signing_key.retired` records attributably
  (`external_custody_rotation`), re-runs are idempotent. Then swap the
  key, restart (keys are read per process), and delete the archive
  after the overlap window.
- **Failures are loud and audited:** a shim failure mid-mint throws
  `RemoteSigningException` — the exchange fails closed with no token
  material — and records a hash-chained `token.signing_failed` row; a
  broken audit store never masks the original signing error.
- **Access TTL honesty:** `Passport::tokensExpireIn` now reads
  `federation.access_token_ttl_seconds` (it was hardcoded 15 min while
  the probe advertised the config), and config clamps
  `AUTH_ACCESS_TOKEN_TTL` to SEC-003's 900 s ceiling — the probe's
  advertised value is always the minted value; env may only shorten
  (e.g. 90 s).

Evidence: `SigningCustodyTest` (9), `RemoteSigningMintTest` (10 —
incl. file-vs-kms claim parity, sentinel validity, no-masking),
`RetireSigningKeyCommandTest` (4), `KmsCustodyExchangeTest` (2 — full
PKCE ceremony under kms custody; dead shim → fail-closed 500 +
audit row), all green beside unchanged `OidcIdTokenTest`,
`SigningKeyRotationDrillTest`, `DiscoveryMetadataTest`,
`RuntimeSeparationTest`, `FederationHostGateTest`. phpstan level 6
clean on every touched path.

Not in this slice (roadmap): HSM/PKCS#11 provider behind the same port,
mTLS/attestation hardening on the shim client, `kid` on kms access
tokens (deliberate league-format parity for now), and the BFF
federation port to `apps/dashboard` + `apps/partner`.

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
  `sid`; ID tokens AND session-bound access tokens embed the same `sid`
  (row + claim, A-SEC-004-6) so revocation can fan out. The ceremony's
  proof facts (`auth_time`, `amr`, `acr`, `sid`) are
  stashed in the session at login, and authorize requests carrying
  `max_age` are measured against them (A-SEC-004-5).
- **Single logout direction (v1):**
  - *RP-initiated (at the platform OP)*: RPs send the browser to the
    platform's `end_session_endpoint` (`/oauth/logout`); the platform
    session is terminated unconditionally, its access/refresh token
    lineage is revoked by `sid` (A-SEC-004-6), and the termination
    fans out to every bound RP over OIDC back-channel logout
    (A-SEC-004-5; received and enforced by each BFF's
    `backchannel-logout` route — A-SEC-004-6).
  - *Platform-initiated*: logging out destroys the platform session and adds
    the session's token lineage to revocation (implemented as the
    `sid`-keyed revoke in `SessionTerminator` — A-SEC-004-6); if the upstream IdP supports
    RP-initiated logout (OIDC `end_session_endpoint` / SAML SLO), the
    platform redirects/posts the logout there (front-channel, best effort).
  - *IdP-initiated*: where the upstream supports front-channel logout, the
    platform exposes `/sso/logout-callback` and destroys the matching
    session via `sid`/subject lookup. Back-channel SLO toward upstream
    IdPs remains out of scope for v1.

---

### Implementation note A-SEC-004-5 — SSO session lifecycle: end-session, back-channel logout, max_age (2026-10-17)

**Status:** ACTIVE · **Supersedes:** nothing (additive; realizes the
OP-side half of "Single logout direction (v1)" above and adds
authorize-time freshness enforcement)

- **End-session endpoint** (`GET|POST /oauth/logout`, throttled
  30/min, CSRF-exempt for the bare form POST; discovery advertises
  `end_session_endpoint`): termination is unconditional and flows
  through the shared `SessionTerminator` — capture `sid`/`sub`,
  invalidate the session, write a fail-closed `session.logout` audit
  (reason codes `end_session_request`, `app_logout`), then fan the
  logout out to bound RPs in a `finally` so the audit survives
  dispatch failures. The redirect target must be an exact match from
  the client's registered `post_logout_redirect_uris` (hash_equals,
  http(s) only): the `id_token_hint` is RS256-verified over current
  AND retired custody keys with `exp` deliberately ignored — logging
  out must work with an expired ID token — and no hint falls back to
  a bare `client_id`. A failed hint never blocks the logout itself —
  the session still dies, the user lands on the neutral logged-out
  page, and a deny audit (`session.end_session_request` /
  `invalid_id_token_hint`) records the attempt.
- **Client registration**: `oauth_client_profiles` gains
  `post_logout_redirect_uris` (JSON list) and `backchannel_logout_uri`;
  both are validated and persisted by the client admin surface and
  exposed in its serialized client payload.
- **Bindings & back-channel fan-out**: consent writes an
  `oidc_session_bindings` row (sid × client × subject; upsert,
  best-effort — telemetry failures report but never fail issuance).
  Termination dispatches OIDC Back-Channel Logout 1.0 to every bound
  client that registered a `backchannel_logout_uri`: each attempt mints
  a fresh `logout+jwt` through the same `SigningCustody` custody as
  A-SEC-003-1 (iss/sub/aud/iat/exp+120/jti/sid, `events` claim, `typ`
  and `kid`; minted per attempt, never stored), sent as
  `application/x-www-form-urlencoded` `logout_token=` with a 3 s
  timeout and no redirects. Outbox table `oidc_backchannel_deliveries`
  records every attempt, backoff [60, 300, 1800, 3600, 7200] s, five
  attempts, then terminal `failed`;
  `auth:retry-backchannel-logout` (`--force`, `--limit`) drains it.
  Delivery-outcome audits are try/catch-wrapped — telemetry, never a
  decision.
- **`max_age` freshness** (`EnforcesMaxAge`, innermost on the authorize
  route — after the PAR bag rewrite, inside the RFC 9207 `iss`
  binding): requests without `openid` scope or without `max_age`
  return early (existing behavior unchanged). A session whose
  ceremony facts (`auth_time`) prove freshness passes; stale or
  unprovable freshness under `prompt=none` fails closed to a
  `login_required` error redirect (only when client and redirect_uri
  validate exactly — otherwise the league-shape 400 JSON), and
  interactively parks an `oidc.reauth` challenge (5-min TTL) plus
  `url.intended` before bouncing to the federation login: the
  signed-in shortcut suspends while challenged, and completing the
  ceremony discharges the flag.
- **Deliberate gaps**: (none open — full `prompt` support closed as
  A-SEC-004-7; RP-side back-channel receivers and OP-side
  token-lineage revocation on logout both landed as A-SEC-004-6.)
- Verified by `EndSessionTest` (8), `MaxAgeEnforcementTest` (8) and
  `BackchannelLogoutTest` (6); full suite green (1084 tests).

---

### Implementation note A-SEC-004-6 — session-bound access tokens + RP-side back-channel receivers (2026-10-17)

**Status:** ACTIVE · **Supersedes:** nothing (additive; closes the two
deliberate gaps A-SEC-004-5 left open — OP-side token revocation on
logout and the RP-side half of the back-channel fan-out)

- **Session-bound access tokens**: the openid ceremony's `sid` rides
  the authorization-code payload (stashed at consent, not read from
  the token-endpoint request — the BFF exchanges server-to-server
  without the browser session), and `OidcAuthCodeGrant`'s
  `issueAccessToken` fork stamps it onto the token entity BEFORE
  persist. `SessionBoundAccessTokenRepository` (container-bound over
  Passport's bridge repository, the same seam as
  `RotatingRefreshTokenRepository`) writes it to the new
  `oauth_access_tokens.sid` column inside the single INSERT, and
  `CustodyAccessToken` embeds the same value as the `sid` JWT claim —
  row and claim travel on one entity, so they cannot disagree. The
  claim appears only on session-stamped mints: unstamped tokens
  (client credentials, non-openid grants) stay byte-equal to league's
  claim set on BOTH custodies, and `RemoteSigningMintTest` pins
  stamped parity too (league layout + `sid` last, file and remote
  alike). Migration `2026_10_17_000004_add_sid_to_oauth_access_tokens`
  (central).
- **Termination kills the lineage**: `SessionTerminator` now revokes
  every access-token row under the terminated `sid` and their refresh
  tokens with them (mirrors `RotatingRefreshTokenRepository`'s two
  updates), fail-closed — the session, the resource-server path and
  introspection all see the death on the next check, and a kept
  refresh token cannot resurrect a terminated session. Machine tokens
  keep a NULL sid and outlive browser sessions by design.
- **RP-side receivers (all four BFF apps, parallel copies)**:
  `POST /api/auth/backchannel-logout` (404 outside federation, bare
  204/400 per spec — never which axis failed). `verifyLogoutToken`
  fails closed on every axis: RS256 pinned, kid-resolved against the
  issuer JWKS; `typ` `logout+jwt` (or the spec's earlier `logout`);
  byte-exact `iss`; `aud` containing our client id; the
  `events` back-channel-logout claim (an id/access token can never
  satisfy the shape); `iat` fresh within 300 s and not future-skewed
  — every delivery attempt re-mints, so a delayed retry never widens
  the window; `sid`/`sub`/`jti` non-empty. The jti is the receiver's
  replay key (bounded in-memory), and a verified `sid` is parked in a
  bounded per-process denylist (24 h TTL, 10k cap; a shared store
  behind the same two functions covers multi-instance).
- **Enforcement choke points**: the denylist is consulted wherever
  the cookie's access token becomes an actor — customer's
  `resolveCustomerActor` (decode path), partner's
  `federatedActorFromToken` (locally verified path), dashboard's
  `verifiedSessionClaims`, and web's `wiredActor` (raw untrusted
  payload decode — the check can only ever REJECT, so a forged cookie
  can at most deny itself). The cookie dies at its next use instead of
  lingering to token expiry; the issuer-side lineage revocation
  remains the primary kill for proxied calls.
- **RP-initiated logout start/landing (all four BFFs, parallel
  copies)**: the browser-facing half of the loop. `GET
  /api/auth/logout/start` (federated only — `404` in direct mode, the
  same parity as the receiver) mints a 32-byte base64url `state` and
  parks it in the flow-family's short-lived HttpOnly `SameSite=Lax`
  correlation cookie (10-min `Max-Age`; `nv-logout-state` per app,
  `__Host-` prefixed in production), then `307`s the browser to the
  issuer's end-session with `client_id` + the profile's registered
  `/api/auth/logout/landing` + `state` — no `id_token_hint`, so no
  token material rides the URL (the OP resolves the RP from the bare
  client id). `GET /api/auth/logout/landing` clears every session
  cookie and the state cookie idempotently on every arrival, then
  timing-safely compares the returned `state` against the parked
  cookie: a match renders the `no-store` signed-out page; a mismatch
  or cold visit keeps the cleanup but `307`s home without
  acknowledgment (a forged link can at most force a self-logout).
  Both are browser-handoff surfaces, so the customer copies join the
  protocol-route envelope exemption; proven live through the customer
  BFF (runbook §10 legs 61–67).
- **Registration**: `platform:client:create` gains
  `--post-logout-redirect-uri=*`, `--backchannel-logout-uri=` and
  `--scope=*` (http(s)-validated logout URIs; the scope option is the
  console's first way to set the A5 `allowed_scopes` ceiling — without
  it a console-created client allows NOTHING and can never pass
  `EnforcesProfileScopes` on `scope=openid`). All four e2e clients in
  `navdhya_e2e_central` (Customer BFF :3001, Web BFF :3000, Scoreboard
  Dashboard :3003, Partner BFF :3002) carry their receiver URIs and
  `openid`; partner's client id/secret live in the gitignored
  `apps/partner/.env.local` (shown once at creation).
- **Live fan-out drill (2026-10-17)**: the full loop ran against real
  processes and MySQL — session-stamped AT `sid` verified from the
  cookie, `GET /oauth/logout` terminated the session (unregistered
  post-logout URI → local sign-out page, never a redirect), all AT/RT
  lineage rows went `revoked=1`, the binding was stamped, and the
  outbox row delivered `204` to the customer receiver after an
  `auth:retry-backchannel-logout --force` drain; the dead cookie then
  `307`'d to login (RP denylist), userinfo rejected both revoked ATs,
  and the refresh grant answered `invalid_grant` "Token has been
  revoked". Two operational rules pinned in the scoreboard runbook §10:
  the retry command must run with the serving issuer's `AUTH_*`
  environment (its re-minted `iss` is byte-compared by the RP — the
  command now prints the issuer it mints with), and logout tokens
  travel as `application/x-www-form-urlencoded` (`logout_token`
  param), which the receivers require. The follow-up multi-RP drill
  (runbook §10 legs 57–59) bound partner :3002 to the same session:
  one logout fanned out to both RPs (partner inline `204`, customer via
  the outbox retry in the same event), and both RPs' cookies died at
  their next use while the issuer rejected both ATs and the refresh
  lineage — the loop's N>1 case proven live. Its follow-up (legs
  61–67) also pinned the D2 staleness bound live: seconds after the
  logout the revoked AT still fetched userinfo (stale positive
  verdict — the lineage kill doesn't invalidate
  `RevocationStatusCache`, convergence = TTL) and was rejected `401`
  once the documented ≤30 s ceiling passed. The scale test (legs
  68–72) then booted web :3000 and dashboard :3003 in federated mode
  for the first time and bound all four BFFs to one session: a single
  RP-initiated logout through customer's own start route stamped all
  four bindings under one sid, delivered four `attempts=1` outbox rows
  inline (all receivers pre-warmed), and killed every RP at once —
  customer `/api/profile`, partner `/api/temples`, web `/api/sessions`
  and dashboard `/api/auth/me` all answered `401` to their explicitly
  presented dead cookies. Three e2e registration fixes surfaced and
  were applied out-of-band first: both dormant client secrets rotated
  (`platform:client:rotate-secret`), dashboard's `redirect_uris` was
  mis-registered to customer's `:3001` callback (fixed to `:3003`),
  and dashboard alone carried `require_par=true` (the OP's RFC 9126
  denial was correct; no BFF could *satisfy* the mandate then — every
  BFF has since gained env-gated PAR push as A-SEC-004-8, and the
  profile matches its three `first_party_web` siblings).
- **Deliberate gaps**: the RP denylist is per-process
  (multi-instance deployments want a shared store). (Full `prompt`
  support closed as A-SEC-004-7. The RP-side *landing* gap closed
  the same day — see the start/landing bullet above and runbook §10
  legs 61–67.)
- Verified by `AccessTokenSessionBindingTest` (3), the extended
  `RemoteSigningMintTest` custody parity (13 with the binding tests),
  the root vitest `tests/identity/backchannel-logout.test.ts` (67:
  the full logout-token rejection battery, receiver route behavior and
  choke-point integrations across all four apps) and
  `tests/identity/rp-logout-landing.test.ts` (32: the start/landing
  pair across all four apps — state correlation, idempotent cleanup,
  forged-link defense, direct-mode `404`s); full issuer suite green
  (1088 tests), phpstan contributes zero errors.

---

### Implementation note A-SEC-004-7 — OIDC prompt enforcement (2026-10-17)

**Status:** ACTIVE · **Supersedes:** nothing (additive; closes the
"full `prompt` support" gap both A-SEC-004-5 and A-SEC-004-6 left
open — the last implementable deliberate gap of the authorize
endpoint)

Passport 12's `AuthorizationController` already implements the
*semantics* of the full `none|login|consent` set natively — silent
approve when a grant is remembered (`prompt=none`, never a consent
page), `login_required`/`consent_required` error redirects when it is
not, a full session destroy plus the `promptedForLogin` resume-flag
loop-guard for `prompt=login`, and a forced consent screen for
`prompt=consent` that outranks an active grant. What it does *not* do
is validate: it collapses `prompt=none <other>` to `none` where OIDC
Core §3.1.2.1 says `invalid_request` MUST, and silently ignores
unknown values — turning a typo'd or unsupported interaction request
into an ordinary flow. That is the exact silent-semantic-loss class
`max_age` enforcement was built to kill at this endpoint.

`EnforcesPrompt` (attached between the PAR rewrite and
`EnforcesMaxAge`, inside `AppendsIssuer`) adds the validation half,
fail closed:

- `none` combined with any other value → `invalid_request`
  (OIDC Core's MUST) — a rejection, never a silent downgrade to one
  of the values, and never a re-auth trigger.
- Unknown value (anything outside `none|login|consent`; notably
  `select_account`) → `invalid_request`: an interaction type this
  issuer does not implement is loud-rejected rather than silently
  downgraded to a plain flow.
- Non-string / array injection (`prompt[]=…`) → `invalid_request`.
- Absent or empty (`prompt=`, which the framework's global
  `ConvertEmptyStringsToNull` normalizes to `null` before route
  middleware runs) → the absent parameter.

Unlike `max_age` (gated to openid scope, because `auth_time` is an
OIDC-only concept), validation applies unconditionally: `prompt`'s
interaction flags need no OIDC claims, Passport applies its native
handling regardless of scope, and a pinned regression test
(`AuthorizationResponseIssuerTest`) requires `prompt=none` on a
non-openid request to answer `login_required` — a no-interaction
request is never bounced to the login page. Error redirects follow
the established league shape: validated client + redirect_uri →
error redirect with `state` and RFC 9207 `iss`; anything less →
league-shape 400 JSON.

Verified by `PromptEnforcementTest` (11: silent approve over a
remembered grant, `consent_required`, forced re-auth with the
loop-guard parked and a full-ceremony resume, forced consent over an
active grant, every validation rejection with state + iss,
scope-unconditional validation, empty-as-absent) and the live legs
73–77 (runbook §11: silent `code` redirect, `consent_required`,
`/auth/login` bounce on a seconds-fresh session, consent screen over
an active grant, both `invalid_request` rejections) — full suite
green (1100 tests), phpstan baseline unchanged (116).

---

### Implementation note A-SEC-004-8 — PAR federation push (BFF side) (2026-10-17)

**Status:** ACTIVE · **Supersedes:** nothing (additive; closes the
capability gap the N=4 fan-out drill surfaced — the issuer has
enforced RFC 9126 per-client (`require_par`, the N2 cohort) since
A-SEC-004-5, but no BFF could *satisfy* a mandate, which made the
mandate unusable in the federation)

Each of the four BFFs (parallel copies) gained a pushed-authorization
client, env-gated per deployment:

- `{CUSTOMER|DASHBOARD|PARTNER}_AUTH_PAR=required` (web shares the
  customer env names, as everywhere in the federation contract): the
  authorize route pushes the full parameter set to the issuer's PAR
  endpoint over `client_secret_post` — the same client-authentication
  posture the callback token exchange uses — and the browser is
  redirected carrying ONLY `client_id` + `request_uri` (RFC 9126 §4:
  every other parameter rode the authenticated back channel and the
  issuer replays the stored set verbatim). `off` (default) and unset
  are the byte-identical front-channel flow; an unknown value throws
  at first use, exactly like `AUTH_MODE` — a typo must never silently
  downgrade a mandate deployment to unpushed parameters.
- Fail closed: a push that errors, or answers without a
  `request_uri`, THROWS (a 500, never a front-channel fallback — that
  fallback is precisely what the mandate forbids).
- The issuer-side mandate stays authoritative: with the customer
  profile's `require_par=true`, a front-channel authorize attempt is
  denied with the established shape (error redirect to the registered
  callback with `state` + RFC 9207 `iss`; unverifiable targets get
  league-shape 400), and a replayed `request_uri` dies with
  `invalid_request / already been used` (single-use).
- The `request_uri` TTL is the issuer's short one (120 s,
  `PushedAuthorizationRequest::TTL_SECONDS`) with an interactive
  ceremony in the middle: the login must complete inside it or the
  resume dies at the OP (400) and the user re-initiates login. No BFF
  recovery is possible at that point — the browser is already at the
  OP holding a dead capability.
- Resume mechanics (the browser contract): the OP's login page PULLS
  the parked `url.intended` (the bounced authorize URL, single-use)
  into the ceremony's `next` and the ceremony posts it back at
  verify — under PAR that resume URL is the `client_id` +
  `request_uri` one, so carrying `next` is what makes the login
  bounce survive a push.

Verified by the root vitest `tests/identity/par-push.test.ts` (36:
the four-app fleet × mode gate, push shape, parameter minimality,
flow-cookie state binding, fail-closed push, 201-without-`request_uri`,
unknown-mode denial, partner's direct-mode target) and the live legs
78–80 (runbook §12: pushed-shape 302, the full OTP ceremony through
the resume, consent, code exchange, `/api/profile` 200, the mandate
error-redirect and the replay rejection) — full root suite 641 tests
with only the known user-WIP failure, partner suite 49/49,
`tsc --noEmit` clean on web/dashboard/partner (customer's only errors
are the user's WIP `@/dal/catalog` import). No issuer-side code
changed; phpstan/phpunit baselines untouched.

---

### Implementation note A-SEC-004-9 — DPoP sender-constrained access tokens (RFC 9449) (2026-10-17)

**Status:** ACTIVE · **Supersedes:** nothing (additive; bearer
tokens on mandated clients become proof-of-possession tokens, and
every client may bind opportunistically)

- **Opportunistic binding:** a `DPoP` header on the
  `authorization_code` or `refresh_token` exchange binds the mint —
  the RFC 7638 thumbprint lands as the JWT `cnf.dpop_jkt` claim AND
  as `oauth_access_tokens.dpop_jkt` on the row (one source, stamped
  before persist, exactly like `sid`), so the resource server never
  decodes anything it cannot verify.
- **`require_dpop` profile mandate** (default `false`): with it on,
  an exchange without a proof is denied `400 invalid_dpop_proof`
  (league shape at the token endpoint) **after** client validation
  and **before** any code touch — a denied exchange never consumes
  the authorization code (proven live: the same code succeeds with a
  proof immediately after the denial). Refresh requires the SAME
  `jkt` as the binding mint — no silent key rotation; the denial
  lands before any rotation write, leaving D4 reuse-detection
  exactly where it was. v1 covers `authorization_code` +
  `refresh_token`; a mandated client asking `client_credentials`
  fails closed at `CheckOAuthGrant` with `unsupported_grant` — never
  silently exempt, never half-covered.
- **Validator:** one seam, `DpopProof::validate($proof, $method,
  $requestUri, $accessToken)` → thumbprint or
  `DpopProofInvalidException`; algs ES256 + RS256; RFC 7638
  canonicalization includes the braces (`{…}`, §3.2 — a production
  bug caught by the test battery); `iat` skew ±60 s; `htu` normalized
  (lowercase scheme/host, default port elided, query/fragment never
  claimed); `ath` checked when presented. Replay is
  `Cache::add('dpop:proof:'.$jti, 1, 300)` executed LAST — only a
  proof that passed every other check spends its `jti`.
- **Enforcement seam:** `PassportJwtVerifier::verify` now returns
  the `cnf` binding (`{dpop_jkt}` or null; a malformed `cnf` fails
  closed — the token is rejected, never downgraded to bearer), and
  `AuthenticateAny::authenticatePassport` validates the presented
  proof after the revocation checks and `hash_equals` it against the
  binding. Covered surfaces: `/oauth/userinfo` and `/api/v1/*` (the
  BFF's upstream calls). A denial is `401` with the platform error
  envelope (`error.code=invalid_dpop_proof`, distinct messages for
  missing / mismatched key / replayed `jti`) plus
  `WWW-Authenticate: DPoP error="invalid_dpop_proof", algs="ES256, RS256"`.
- **BFF client (canonical implementation):** `apps/customer/server/dpop.ts`
  holds one EC P-256 key per instance (ES256, IEEE P1363), attaches a
  proof to the callback token exchange and to every upstream bearer
  call (`ath` over the presented token) — mandate-transparent by
  construction. Key lifetime is the per-instance FILE store
  (amended by A-SEC-004-11 after Next's proxy/handler module-graph
  split proved a memory-only cache holds two keys): a restart KEEPS
  the binding key; only a wiped or unreadable key file drops it —
  bound sessions then fail upstream with 401 and re-login recovers;
  multi-instance deployments need a shared or stickily routed key
  store (documented production extension, same posture as the other
  per-instance state notes).

Verified by phpunit `DpopBindingTest` (21: bind, mandate, machine
fail-closed, resource denials, refresh same-jkt) and the root vitest
`tests/identity/dpop-binding.test.ts` (12: proof shape + signature,
callback exchange wiring, upstream `ath` wiring), plus live legs
81–85 and the mandate-recovery probe (runbook §13) against the real
issuer: bind → `cnf` match, userinfo accept/deny/replay, mandate
400-without-proof → 200-with-proof on the SAME code, refresh
same-key-only, and the full customer BFF login under the mandate
ending in an upstream `200`. Gates: phpstan 116 = baseline, phpunit
1124 OK, root vitest 652/653 (only the known user-WIP `MobileHome.tsx`
boundary failure), customer `tsc` clean except the user's WIP
`@/dal/catalog` imports.

---

### Implementation note A-SEC-004-10 — refresh lineage sid continuity (2026-10-17)

**Status:** ACTIVE · **Supersedes:** nothing (closes an open hole in
A-SEC-004-6's lineage claim — flagged by every slice since, now
fixed)

A-SEC-004-6 made session termination revoke a session's whole token
lineage "by sid", and the terminator revokes the access tokens where
`sid = X` together with the refresh tokens hanging off them. But
refresh rotation minted its successor WITHOUT a sid — so a family
that rotated before the logout produced an access token (and a
refresh token) sitting OUTSIDE that `where sid =` net: the successor
survived the kill, and its refresh token could mint further tokens
AFTER the session ended. RP-side sid denylists had the same blind
spot: a sid-less refreshed token never matched a terminated session.

- **One lineage source, faithful carry-forward:**
  `RecoverableRefreshTokenGrant::rotateAtomically` now reads the
  ORIGINAL token's row once (the same row that has always supplied
  the family's DPoP `dpop_jkt`) and carries BOTH values onto the
  successor — the sid lands via the exact entity-stamp pipeline
  A-SEC-004-6 built (`CustodyAccessToken::withSessionId` →
  `SessionBoundAccessTokenRepository` → row column + JWT `sid` claim
  from one source). A family that never had a session (non-openid
  codes, machine grants) keeps a NULL sid — the carry is faithful,
  never invented lineage. The fork's `issueAccessToken` mirrors
  `OidcAuthCodeGrant::issueAccessToken`'s stamp-parameter order
  (`$sessionSid`, then `$dpopJkt`), so both grants stay in sync by
  shape.
- **No terminator change needed:** with the stamp present, the EXISTING
  kill net covers both escape vectors — the successor access token
  dies in the `where('sid', …)` fan-out and its refresh token dies in
  the `whereIn('access_token_id', …)` half of the same statement. A
  refresh attempted AFTER the kill was already denied (the kill
  revokes the family's refresh tokens; reuse detection answers
  `invalid_grant`), and that denied presentation additionally fires
  the deliberate SEC-003 §3.2 user+client-wide reuse kill.
- **DPoP coexistence:** rotation of a bound family keeps both stamps
  (`cnf.dpop_jkt` + `sid`) from the one entity stamp — pinned by test
  and by live leg 88.

Verified by four new `AccessTokenSessionBindingTest` cases (sid rides
the successor across rotations; kill-after-rotation revokes the
successor AT + RT and answers `invalid_grant` on the kept refresh
token; sid-less families stay sid-less; DPoP + sid coexist across
rotation) — all four FAILED against the pre-fix grant, exactly on
the hole. Gates: phpstan 116 = baseline, phpunit 1128 OK. Live legs
86–88 (runbook §14): sid carried across two rotations, logout killed
a rotated lineage (successor 401 at userinfo, kept RT
`invalid_grant`) while a different session's family survived the
logout itself, and a bound family kept `cnf` + `sid` through
rotation.

---

### Implementation note A-SEC-004-11 — BFF refresh rotation (D4 browser half) (2026-10-18)

**Status:** ACTIVE · **Supersedes:** nothing (closes runbook §5 gap
"No BFF-side refresh rotation yet (D4)"; the issuer half of D4 —
`RecoverableRefreshTokenGrant` + refresh idempotency + reuse kills —
was already in place)

Until now the customer BFF discarded the refresh token at the
callback: the access token's 900 s TTL ended the browser session and
the user re-entered the login ceremony. A-SEC-004-11 completes D4's
browser half — the BFF holds the refresh token and rotates the family
server-side, lazily, in the request path.

- **Cookie posture:** the refresh token rides a SECOND HttpOnly
  cookie, `nv_customer_refresh` (`__Host-` prefixed under
  `SESSION_COOKIE_SECURE`), SameSite=Lax, 30-day max-age = the
  issuer's `Passport::refreshTokensExpireIn`. The access cookie is
  unchanged (900 s). A rotation-pending cookie `nv_customer_rot`
  (60 s = `federation.refresh_retry_window_seconds`) exists only
  while an idempotency key awaits replay.
- **One choke point — Next 16 `proxy.ts`:** `middleware.ts` was
  renamed (Next 16 deprecates it; the proxy runs the node runtime,
  which `node:crypto` DPoP needs). Before the anonymous gate decides,
  `ensureFreshSession` inspects the access cookie: live beyond the
  60 s skew → untouched; near/over expiry with a refresh cookie →
  rotate; no access cookie but a refresh cookie → resurrect. A
  successful outcome REWRITES the incoming request's cookie header
  (`NextResponse.next({request:{headers}})`) so every downstream
  handler and upstream call sees the fresh pair and never presents a
  token the issuer has already consumed — zero route edits.
- **Rotation engine (`server/session-refresh.ts`):** POST
  `grant_type=refresh_token` (client_secret_post) with an
  `Idempotency-Key` per exchange and a DPoP proof from the instance
  key — bound families REQUIRE it. Signature-free `exp` decode
  matches the platform's bearer posture. Single-flight (`inflight`
  map) plus a 30 s recent-replay cache keyed by sha256(RT) makes the
  double-rotation — the SEC-003 §3.2 user+client lineage kill —
  unreachable from concurrent requests in one instance. On transport
  failure the SAME key retries in place once, then parks in the
  rotation-pending cookie for the next request to replay; the issuer
  (`RecoverableRefreshTokenGrant`) dedups by fingerprint
  (client_id + refresh_token + scope, no `scope` parameter sent —
  fingerprint stability) across the retry window.
- **Outcomes, fail-closed:** 200 + full pair → rotated (or
  resurrected when no access cookie was present); any 4xx
  (`invalid_grant` — lineage dead from an OP logout or a consumed
  family; `invalid_dpop_proof` — lost key) → fatal: BOTH cookies
  cleared, browser re-auth; 5xx/transport → retry-later: cookies
  untouched, parked key replays next request; 200 WITHOUT a refresh
  token → fatal (never a bearer-only downgrade). Fatal and deferred
  decisions log the OAuth error code — never token material.
- **Logout:** the BFF route revokes BOTH credentials at the issuer
  (refresh hint `refresh_token`, then access) and clears both
  cookies — a logged-out browser jar holds no session credentials by
  construction (live leg 92).
- **DPoP key store (A-SEC-004-9 amendment):** Next splits the proxy
  and route handlers into SEPARATE module graphs — a module-local
  key cache holds TWO keys, and the proxy's rotation proof misses
  the family's `cnf.dpop_jkt` (observed live: every rotation 400
  `invalid_dpop_proof`). The instance key is now FILE-backed
  (`$CUSTOMER_DPOP_KEY_FILE`, default `<cwd>/.next/dpop-key.json`,
  `O_EXCL` claim + reload-on-miss, corrupt file regenerated once):
  one key per instance across every graph and worker thread. A
  process restart no longer drops the binding key — sessions survive
  restarts; only a wiped or unreadable key file forces re-login. An
  unwritable filesystem falls back to an in-memory key (logged) —
  single-graph auth works, cross-graph rotation fails closed.

Verified by `tests/identity/refresh-rotation.test.ts` (25: cookie
family + names both casings, freshness skew, rotation/resurrect/
fatal/retry-later outcomes, single-flight, replay denial, idempotency
parking, proxy wiring, logout dual-revoke) and 3 new key-store cases
in `tests/identity/dpop-binding.test.ts` (a second module instance
proves with the persisted key; restart-stable; corrupt file
regenerated). Customer `tsc` clean except the user's WIP
`@/dal/catalog` imports. Root vitest 680/681 (only the known user-WIP
`MobileHome.tsx` boundary failure). No PHP changes — phpstan 116 and
phpunit 1128 hold by inspection of an untouched tree. Live legs
89–92 (runbook §15): login stores the refresh cookie; a crafted
near-expired access token rotates server-side (fresh pair with
900 s + 30 d TTLs, real upstream data); an idle browser resurrects
from the refresh cookie alone; OP logout kills the rotated lineage —
sid denylist 401s the next call, a post-expiry rotation answers
`invalid_grant`, both cookies clear, and the retired refresh token
is refused at the issuer.

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

### Implementation note A-SEC-015-3 — fail-closed revocation & introspection (2026-10-16, phase 9c)

**Status:** ACTIVE · **Supersedes:** nothing (additive; tightens the
behavior described by A-SEC-015-1/2 in place, per ADR-0023)

- RFC 7009 §2.1 ownership is enforced on `/api/oauth/revoke`: a client
  may revoke only tokens it owns (access through the row's `client_id`,
  refresh through its access token's client). A foreign client gets
  league-shaped `400 {error:"invalid_grant"}` and no kill happens;
  unknown/undecryptable tokens keep the silent `200` (no oracle).
- User **deactivation** now cascades like suspension (§8.1: all
  tokens, all sessions), and `AuthenticateAny` gates lifecycle status
  on the session-shaped branch too — a direct status flip with intact
  rows fails closed at the enforcement point.
- Both console revocation commands (`platform:client:revoke`,
  `platform:client:rotate-secret`) invalidate the revocation-status
  cache exactly like their HTTP mirrors — first-party invalidation is
  0 s on **every** site (ADR-0020 D2), not TTL-bounded.
- Every executed kill lands on the D5 hash chain as a per-jti
  `token.revoked` row with its reason (`token_revoked`,
  `client_revoked`, `secret_rotated`, `user_suspended`,
  `user_deactivated`, `membership_revoked`); refusals record nothing.
- Membership deactivation revokes that tenant's refresh lineages at
  rest (client-profile `tenant_id` mapping) instead of relying on the
  live re-check alone.
- Introspection (§8.2) fails closed: signature verified first,
  dead/missing-row JWTs and `expires_at IS NULL` answer `active:false`,
  and both legs re-check owner liveness — introspection now agrees
  with dual-accept instead of advertising a credential the audience
  would refuse.
- Still future work, unchanged: `jti_revocations` table, Redis
  revocation cache (REDIS-001), back-channel SLO.

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
