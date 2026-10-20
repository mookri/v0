# ADR-0022: Machine-grant scope ceiling — A5 extended to client-credentials, refresh and token-exchange

**Status:** Accepted · **Date:** 2026-10-15 · **Deciders:** Platform Architect

## Context

ADR-0021 (phase 9a) introduced the A5 per-profile `allowed_scopes`
ceiling and enforced it at the three **auth-code** seams — authorize,
auth-code token exchange and PAR push. That ADR deliberately left a
boundary open and recorded it: `client_credentials`, `refresh_token`
and the RFC 8693 token-exchange grant were **not** guarded, because
they mint no ID tokens and never cross the auth-code seams.

The boundary was correct for 9a but is a live gap: all three grants
mint access tokens whose scopes decide what a machine client may do.
The registry check (league's `validateScopes`) answers "is this scope
registered at all"; only the profile ceiling answers "may THIS client
ask for it". Without the ceiling, a profile narrowed after a token was
minted — or a client whose profile never allowed the scope — could
still obtain or retain machine scopes through refresh inheritance
(subset rule compares only to the old token), client-credentials
requests (registry-only check) or exchanges (subset rule compares only
to the subject).

Phase 9b closes that boundary: the **same** `EnforcesProfileScopes`
trait now governs all six seams, so the rule cannot drift between the
auth-code side and the machine side. This ADR supersedes ADR-0021's
"documented, not yet guarded" boundary note; nothing else in ADR-0021
or ADR-0020 changes.

## The binding decisions

### M1 — One trait, six seams

`EnforcesProfileScopes` gained a shared `enforceProfileScopes()` (the
league-`invalidScope` thrower, redirect-aware) and now sits on every
grant that mints tokens:

| Seam | Grant class | Insertion point | Failure shape |
| --- | --- | --- | --- |
| Authorize (9a) | `OidcAuthCodeGrant` | `validateAuthorizationRequest` | league `invalid_scope` 302 (+ state, `iss`) |
| Auth-code token (9a) | `OidcAuthCodeGrant` | after `validateScopes`, before `finalizeScopes` | league `invalid_scope` 400 JSON |
| PAR push (9a) | `PushedAuthorizationRequestController` | `validateProfileScopes` | `invalid_scope` 400 JSON |
| **Client credentials (9b)** | `AssertionCapableClientCredentialsGrant` | forked `respondToAccessTokenRequest`, after `validateScopes` | league `invalid_scope` 400 JSON |
| **Refresh (9b)** | `RecoverableRefreshTokenGrant` | `rotateAtomically`, after the subset-of-old rule | league `invalid_scope` 400 JSON |
| **Token exchange (9b)** | `TokenExchangeGrant` | after `validateScopes`, on the **presenting** client | league `invalid_scope` 400 JSON **+ chained deny** |

In every case the ceiling runs **after** the registry check and after
the grant's own narrowing (subset/inheritance), and **before**
`finalizeScopes` — the same position the 9a auth-code token seam uses.
`finalizeScopes` only ever filters, never adds, so nothing can
materialize a scope between the ceiling and the persisted token. The
checked client is always the **authenticated** client — for the
exchange that is the presenting client, never the subject's client.

### M2 — Deny shape is per-grant (ratified split)

- **Client credentials and refresh**: plain league `invalid_scope`
  400 JSON, **no audit row** — identical posture to the 9a auth-code
  token seam. A machine scope misconfiguration is protocol surface, not
  a chained authorization event.
- **Token exchange**: the denial is an authorization outcome at the
  BFF delegation boundary and lands on the hash chain (D5) with the
  new `reason_code = scope_not_permitted`, bound to the presenting
  client — consistent with the grant's existing audited scope denial
  (`scope_not_subset`).

### M3 — Fail-closed semantics (unchanged from A5, extended in reach)

- A profile with empty `allowed_scopes` may mint only **scope-less**
  machine tokens; a missing profile is already rejected upstream by
  `CheckOAuthGrant` (profiles are mandatory at the token route).
- An empty requested set always passes — the scope-less machine norm
  (introspection services, BFF client-credentials tokens) is untouched.
- Refresh re-asserts the ceiling against the **live** profile, so a
  narrowed profile stops carrying revoked entitlement forward; the
  subset-of-old rule still applies first (RFC 6749 §6).
- Token-exchange requested scopes must fit **both** rules: a subset of
  the subject's scopes (existing) and within the presenter's ceiling
  (new). A presenter may always narrow; it may never widen.

## Compatibility and rollout

- All existing machine flows are scope-less, and an empty set passes
  the ceiling — the change is purely additive; the full suite proves
  it (no fixture migrations).
- Going forward, a machine client that legitimately needs a scope must
  have it in its profile `allowed_scopes` (via `platform:client:create`
  management or the OAuth client surface). Registering a scope in
  Passport's registry is no longer sufficient for machine grants — by
  design.
- Refresh denial happens **before** the rotation transaction: no half
  state, the presented refresh token is not consumed, and restoring
  the profile revives it (pinned by test).

## Consequences

- `EnforcesProfileScopes` is now used by four grant classes and one
  controller; the ADR-0021 boundary note is superseded.
- `AssertionCapableClientCredentialsGrant` is a body-for-body fork of
  league's `ClientCredentialsGrant` (one insertion) — kept in sync on
  league upgrades like the other grant forks.
- No discovery, PAR, `iss`, ID-token or BFF change: the machine
  ceiling is invisible to clients until a profile actually narrows.
- The FAPI refresh lifecycle remains deferred to 9g (F9); this ADR
  touches only the scope ceiling.

## Evidence

- New suite `MachineGrantScopeCeilingTest`: **9/9 green** (client
  credentials deny/allow/norm, refresh narrowed-deny + lineage
  revival/re-allow/norm, exchange presenter-ceiling deny + chained
  `scope_not_permitted`/allow/explicit-narrowing).
- Full Laravel suite: **1007/1007 green** (9a baseline 998; +9).
- pint clean; jest federation untouched (35/35).
