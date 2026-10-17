# ADR-0023: Fail-closed revocation — RFC 7009 ownership, deactivation bulk trigger, console invalidation parity, D5 chain, tenant lineages, introspection liveness

**Status:** Accepted · **Date:** 2026-10-16 · **Deciders:** Platform Architect

## Context

Phase 9c of the Unified Auth v3 plan audits the revocation surface
against contracts already ratified: ADR-0020's D2 ("every first-party
revocation site invalidates immediately, 0 s; cross-process revocation
bounded by the 30 s TTL") and D5 (every authorization-relevant
transition emits a chained structured audit event), plus SEC-015 §8.1's
bulk-trigger list and RFC 7009 §2.1/§5's ownership requirement.

Research verified six gaps between those contracts and the shipped
code, each traced to failing behavior rather than speculation:

- **F1** `RevokeController` revoked any token its caller presented —
  no RFC 7009 §2.1 ownership check — and the raw-jti primary-key
  fallback let a foreign client probe and kill other clients' tokens.
- **F2** user **deactivation** skipped `revokeCredentials()` (only
  suspension cascaded) and `AuthenticateAny`'s session-shaped branch
  never checked lifecycle status, so a deactivated user's web session
  survived indefinitely — the one true fail-open on the enforcement
  path.
- **F3** the console revocation commands (`platform:client:revoke`,
  `platform:client:rotate-secret`) skipped `RevocationStatusCache`
  invalidation while a test comment claimed "console/HTTP" fired the
  hook — a ≤30 s stale-positive window where D2 promises 0 s.
- **F4** no D5 chained event existed on any revocation site (the
  listeners were reuse/otp/issued only) — revocation was silent by
  construction.
- **F5** membership deactivation never revoked that tenant's refresh
  lineages: the live re-check denied in-request, but an unexpired
  refresh token kept minting forever.
- **F6** introspection trusted any JWT-shaped string — no signature
  check, `expires_at IS NULL` counted as not-expired, and a suspended
  owner's live row still answered `active:true`.

Phase 9c closes all six in one pass. Ratified scope: C1–C6 in full,
with D-9c = **400 `invalid_grant`** for foreign-token revocations
(the RFC's literal shape) while unknown/undecryptable tokens keep the
silent `200` (no oracle — unchanged). Nothing else in ADR-0020/0021/0022
changes; the `jti_revocations` table, a Redis revocation cache and the
back-channel SLO remain SEC-015 future work.

## The binding decisions

### C1 — RFC 7009 §2.1 ownership: revocation is owner-scoped

- Ownership is resolved **before** the kill: access tokens through the
  row's `client_id`, refresh tokens through their access token's
  `client_id`. The authenticated client must equal it.
- A foreign client presenting someone else's token gets RFC 7009's own
  error: `400 {error:"invalid_grant", error_description:"the token was
  issued to another client"}` in league's protocol shape — no
  revocation, no cache invalidation, no chain row (a refusal executes
  no kill).
- Unknown or undecryptable tokens still answer a silent `200` (no
  oracle — unchanged from A-SEC-015-1). The only callers who can see
  the `invalid_grant` distinction already hold the full token string,
  which RFC 7009 §5 weighs as the scenario that matters.

### C2 — Deactivation is a bulk trigger, and the enforcement point gates it

- `UserAdminController::setStatus` now calls `revokeCredentials($userId,
  $reason)` for **suspended and deactivated** alike — SEC-015 §8.1
  names both ("user suspension/deactivation: all tokens, all
  sessions").
- `AuthenticateAny`'s session-shaped branch passes only an active
  principal: `! $user instanceof User || $user->isActive()`. Non-User
  principals (API keys, partner/admin key types) pass through
  unchanged. This is defense in depth for direct status flips,
  non-database drivers and long-lived worker processes — even where no
  sweep ever ran, the gate refuses.

### C3 — Console revocation sites invalidate like the HTTP mirror

- `RevokeClientCommand`: `invalidateClient` + `invalidateTokens` over
  the full sweep set. `RotateClientSecretCommand`: `invalidateTokens`
  only — a rotated secret keeps the client live, so its liveness
  verdict must **not** be invalidated.
- With the default database store the invalidation is shared across
  processes at once; D2's "first-party = 0 s" now holds for console
  sites, and the stale-positive window (≤30 s) is gone.

### C4 — Every executed kill lands on the D5 hash chain

One `token.revoked` event per revoked access-token `jti`, `decision:
allow`, carrying session-scoped pseudonymous refs (`sub`/`jti` HMAC —
raw ids never land) and its reason code:

| Reason | Site |
| --- | --- |
| `token_revoked` | RFC 7009 `POST /api/oauth/revoke` (owner path) |
| `client_revoked` | `platform:client:revoke` + `OAuthClientController::revoke` |
| `secret_rotated` | `platform:client:rotate-secret` |
| `user_suspended` | `UserAdminController` suspend sweep |
| `user_deactivated` | `UserAdminController` deactivate sweep |
| `membership_revoked` | customer profile deactivation (C5) |

Refusals (C1 foreign, unknown tokens) record nothing — the chain
describes executed kills, never attempts. The pre-existing
`session.reuse_detected` single-event kill is untouched; a per-jti
recording replaces no count column because none exists.

### C5 — Membership deactivation revokes that tenant's lineages

- Tokens carry no tenant claim; the only tenant signal is the client
  profile's `tenant_id`. `CustomerProfileController`'s two OTP-stepped
  deactivation sites now call `revokeTenantLineages($userId, $tenantId,
  'membership_revoked')`: every access token that user minted through a
  client whose profile belongs to the tenant, plus its refresh half, is
  revoked, invalidated and chained with `tenant_ref`.
- Other tenants' lineages and tenant-less platform clients are
  untouched — pinned by test (a second tenant's token survives).

### C6 — Introspection fails closed

- **Signature first**: the access leg runs `PassportJwtVerifier` before
  trusting anything — a forged signature can no longer borrow a live
  row's claims (the refresh fallback still applies only to genuinely
  decryptable refresh tokens).
- A verified-signature JWT whose row is dead or missing answers
  `active:false` directly — no fallback resurrection.
- `expires_at IS NULL` ⇒ treated as expired: a row without a bounded
  lifetime can never be active.
- Owner liveness: access **and** refresh legs re-check
  `User::isActive()`, so introspection agrees with dual-accept instead
  of advertising a credential the audience would refuse. Machine tokens
  (no owner row) skip the owner leg.
- Verified-JWT claims (`scopes`) win over row scopes — the row may lag
  a narrowing.

## Compatibility and rollout

- Owner-credential flows are unchanged; unknown-token `200` is
  unchanged; the only observable breaks are the ratified ones: foreign
  revocations now `400`, deactivated users' sessions die, console sites
  bite at 0 s.
- `IntrospectTokenTest`'s revoke leg pinned the pre-9c cross-client
  behavior (a service client revoking the web client's token) — moved
  to owner credentials, the RFC-conformant shape.
- Four fixtures gained the kit-convention per-test
  `identity.hmac_secret` because sweeps now record (`OAuthClientTest`,
  `OAuthClientHttpTest`, `RevocationStatusCacheTest`,
  `IntrospectTokenTest`); zero-token sweeps record nothing, so
  unaffected fixtures were left alone.
- The membership hook is additive: with no live tokens it no-ops, so
  existing customer tests needed no change.
- Test-executor note: `AuthenticateAny` sets the authenticated
  principal on the shared guard, so bearer legs in feature tests must
  `auth()->logout()` between requests (house convention, re-verified
  here) — otherwise a previous request's principal leaks into the next
  request's session branch through the shared container.

## Consequences

- Revocation is owner-scoped: a compromised low-privilege client can
  neither probe nor kill other clients' tokens (F1).
- Deactivation is a true bulk trigger; even a direct status flip with
  intact rows fails closed at the enforcement point (F2).
- Console and HTTP revocation sites share one 0 s invalidation
  contract — the D2 promise is uniform (F3).
- The chain gains one row per revoked jti, bounded by live tokens per
  client; token-less sweeps record nothing (F4).
- Membership revocation is enforced at rest, not just at read time: no
  more endless refresh minting after a tenant deactivates a member
  (F5).
- Introspection can no longer launder forged or dead credentials into
  `active:true` (F6).
- `token.revoked` becomes the highest-volume chained event type by
  design (per-jti rows); it stays queryable by `reason_code` +
  `actor_client_id` + `token_jti_ref`.

## Evidence

- New suite `FailClosedRevocationTest`: **11/11 green** (foreign
  access/refresh refusal + survival, deactivation cascade + session
  gate, console revoke/rotation invalidation + chain, RFC 7009 chain
  with pseudonymous jti, tenant-lineage kill + other-tenant survival,
  introspection forged-signature / suspended-owner / null-expiry).
- Full Laravel suite: **1018/1018 green** (9b baseline 1007; +11).
- pint clean; jest federation untouched (35/35).
