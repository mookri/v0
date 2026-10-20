# ADR-0020: Unified authentication and federation (auth subdomain, six binding decisions)

**Status:** Accepted · **Date:** 2026-10-14 · **Deciders:** Platform Architect

## Context

Auth v3 centralizes Navdhya identity on an authorization-server subdomain
(`auth.navdhya.com`, locally `auth.localhost`) backed by the same Laravel
modular monolith (Passport 13 + the OAuth layer under
`app/Platform/Identity/OAuth`). The six decisions below are **binding**:
every register that implements auth behavior conforms to them, and each
ships with the enforcement mechanism and tests stated here — a decision
without an enforcement test is not implemented.

Earlier plan shorthand ("ADR-004") maps to this ADR; repository numbering
is authoritative (ADR-0020).

## The six binding decisions

### D1 — Identity authority

`auth.<domain>` is the sole issuer of Navdhya identity and federation
tokens. All security-sensitive claims (`iss`, `sub`, `aud`, `scope`,
`amr`, `acr`, risk tier, `sid`, `jti`, `iat`, `exp`) derive from
server-side state; clients may request but never determine what they
receive. Tenant administrators change memberships and roles only through
authorized administrative operations; they cannot fabricate verified
identity, authentication strength, trusted-device status or risk
outcomes. Machine tokens represent clients, not humans; delegated
user-context tokens identify subject and (where applicable) actor, and
both are validated on RFC 8693 exchange.

*Enforcement:* claim authority is code paths in the issuer; compromise of
one BFF must not allow issuing identity claims, minting tokens,
impersonating another client, or selecting an unregistered audience.

### D2 — Revocation

Two policy classes:

- **Ordinary API calls:** revocation propagates to resource-server
  authorization decisions within **30 seconds** of the authoritative
  revocation commit. A token's remaining lifetime never exempts this.
  Resource servers verify signatures locally (JWKS) but hold session/
  revocation status under a **hard 30 s freshness bound**; revocation
  events invalidate sooner; stale status with no authoritative refresh
  ⇒ deny.
- **Critical operations** (privilege changes, security-setting changes,
  high-impact financial operations): current session/account/authorization
  state is checked at the enforcement point (authoritative read). No
  decision relies solely on an unexpired cached token. Where revocation
  races a state-changing operation, the authorization decision serializes
  with the operation.

Risk owner approves any permitted delay; the bound is recorded here and
verified by automated tests under lag, partition and cache failure.

### D3 — Private keys

The authorization server's token-signing key and per-client
client-authentication keys have **separate owners, storage and
permissions**. Each BFF holds its own key pair; the public half is
registered against that client only. Resource servers receive public keys
only through JWKS. Key administration and rotation is attributable and
audited. Blast radius:

| Compromise | Permitted | Forbidden |
|---|---|---|
| One BFF client key | Impersonation of that client within its allowed grants | Any other client; signing-key access |
| Resource server | That service's runtime and accessible data | Minting Navdhya tokens |
| Signing key | Issuer-wide forgery risk — critical incident | — mitigated by custody, revocation, rotation runbooks |

*Required test:* compromise simulation for one BFF proves token issuance
for a different client remains impossible.

### D4 — Refresh & outages

Refresh tokens rotate atomically (old consumed, successor created, one
MySQL transaction). Reuse of a consumed token with mismatched
idempotency/fingerprint ⇒ family revocation + `session.reuse_detected`
audit event. A retry with the **same** client + idempotency key + request
fingerprint may recover the original response inside a narrowly bounded
retry window (lost-response recovery — not a replay grace period).
Refresh tokens are stored hashed; any recoverable response material is
encrypted, access-controlled and short-lived. BFFs coordinate refreshes
per session (single-flight).

Failure policy (each cell is a test):

| Failure | Behavior |
|---|---|
| Redis unavailable | Defined MySQL fallback for replay/throttle; never bypass a security check |
| MySQL unavailable | No issuing/rotating refresh tokens without authoritative state; recovery or re-auth |
| Signing unavailable | No unsigned/degraded tokens |
| JWKS refresh unavailable | Continue with previously trusted still-usable keys where permitted; reject unknown-key tokens |
| Revocation status unavailable | Ordinary APIs stop relying on stale status after 30 s; critical ops fail closed immediately |
| SMS provider unavailable | Controlled authentication failure; never treat unverified OTP as success |

### D5 — Auditability

Every authentication, token issuance/exchange, authorization denial,
refresh, revocation and privileged policy change emits a structured,
correlated, **non-secret** audit event (`auth_events`, append-only,
hash-chained, periodic independent checkpointing, automatic chain
verification). Events carry actor, subject (HMAC-pseudonymous), client,
tenant, requested vs granted scopes, target audience, decision, reason
code, policy versions, `jti` **reference** (never the token), request and
trace ids. Tokens, refresh tokens, OTPs, passwords, cookies and private
keys are never logged.

*Limit of record:* the chain detects tampering; it does not protect
against a privileged attacker rewriting the whole chain — independent
checkpoints, restricted audit administration and separate monitoring
carry that residual risk.

*Required test:* given a token reference + trace id, the issuance and
authorization path reconstructs without the bearer token or unnecessary
PII.

### D6 — Service extraction

Same repository by default; the auth surface may run as an independently
configured runtime with scoped credentials and resource limits.
Extraction happens only via (A) mandatory isolation — a binding
security/regulatory/containment requirement — or (B) measured operational
benefit: at least two quantitative triggers (reliability coupling, scaling
pressure, deployment friction, independent ownership — thresholds
recorded in the extraction runbook) persisting across two consecutive
monthly reviews, with a costed extraction plan. Independent deployment,
rollback, scoped key access, ownership, migration ownership, SLOs,
restore drills and client contract tests are prerequisites, not outcomes.

## Consequences

- `config/federation.php` carries the domain, issuer, 30 s revocation
  bound, refresh retry window, clock-skew tolerance and key custody
  paths; boot fails closed where a federation deployment is asserted but
  misconfigured.
- The existing HMAC actor-token bridge (`ActorTokenIssuer`) remains
  valid for direct-mode apps during strangler cutover; it is a *legacy
  credential*, not a second issuer — every minted session is still
  recorded (D5) and revocable (D2).
- Resource-server checks gain an explicit freshness contract
  (`RevocationStatusCache`, D2) instead of implicit per-request reads.

## Evidence

The P8 live drill executed every federation leg against the e2e stack
(federation login with real SMS, PKCE ceremony, consent, exchange,
userinfo, introspection, refresh rotation + reuse detection,
revocation, SSO re-approval, both BFFs end-to-end in a real browser).
Full scoreboard, curl conventions and observed responses:
`docs/22-runbooks/unified-auth-curl-scoreboard.md`. Contracts the drill
nailed down: league access tokens carry **no `iss`** (signature is the
issuer binding; `aud` is the presenting client), the revocation
endpoint authenticates clients with **body credentials**
(client_secret_post — Basic is refused before any revocation), and
consent is re-required once a client's token lineage is fully dead.

## Superseded in part by ADR-0021 (phase 9a)

ADR-0020's finding that "league access tokens carry **no `iss`**"
remains true for the **access token** and is unchanged. Phase 9a
(ADR-0021) adds `iss` on a **different surface**: the RFC 9207 `iss`
query parameter on every redirect-based authorization response (success
and error alike) and the `iss` claim on the OIDC **ID token**. The
league access token itself still carries no `iss`; the BFF session
decode continues to direction-check `aud`, and the authorization
*response* and *ID token* are now bound to the issuer by `iss`. See
ADR-0021 *Supersession* for the full statement.
