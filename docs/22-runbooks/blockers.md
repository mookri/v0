# Blocker Register (living document)

Infrastructure or decisions blocking planned work. Each entry: what is
blocked, the unblock condition, and the interim posture. Resolved entries
move to the bottom with date + resolution, never deleted.

## Open

### REDIS-001 — No Redis server or PHP extension (2026-10-01)
- **Blocks:** tiered rate limiting (§6.2), shared sessions, Redis event bus,
  decision-cache distribution, queue driver beyond sync/database.
- **Unblock condition:** provisioned Redis + `redis` ext (or `predis`
  decision recorded) in dev, staging, and CI service containers.
- **Interim posture:** rate limits documented but unenforced (except
  `throttle:10,1` on invite-accept, which is driver-agnostic);
  memory/file-backed sessions, bus, and stores with single-instance
  limitation notes in code. Nothing pretends to be distributed.
- **Tracks:** rate-limiter slice, shared-session swap, Redis bus binding.

### GIT-001 — Backend repo has no remote (2026-10-01)
- **Blocks:** review, CI execution, collaboration.
- **Unblock condition:** create remote + push `main` (baseline commit
  `60d5011` ready). No `gh` CLI on this box — create via provider UI.
- **Interim posture:** local git history only; explicit-path staging for
  every commit until pre-commit hooks exist.

### MAIL-001 — No mail provider (2026-10-01)
- **Blocks:** password resets, OTP-via-email fallback, reviewer
  notifications, invite delivery emails.
- **Unblock condition:** provider selected + log-driver in dev/staging.
- **Tracks:** set-password flow, notification dispatcher.

### VAULT-001 — Secrets in env files (2026-10-01)
- **Blocks:** production credential handling (Razorpay, tenant DB configs,
  session pepper, HMAC secrets).
- **Unblock condition:** vault path per security baseline §3 + rotation runbook.
- **Tracks:** payment production creds, dedicated tenant configs.

### TOKEN-001 — Passport JWT unbuilt (2026-10-01)
- **Blocks:** login-response upgrade, SSO, scoped partner tokens.
- **Interim posture:** HMAC dual-accept bridge (verifier + middleware,
  cross-implementation tested). Customer self-service auth (B2) mints
  backend HMAC actor tokens (`ActorTokenIssuer`, 24 h absolute, no
  server-side revocation until the jti store lands) — same interim.
- **P1 done (2026-10-02):** `laravel/passport` installed, keys generated
  (dev-local; prod via VAULT-001), stock `oauth_*` tables on central,
  routes OFF (`ignoreRoutes`) until P2 configures clients/grants.
- **P2 done (2026-10-02):** routes on with consent screen; code + PKCE
  happy path, PKCE mismatch denial, refresh rotation with reuse
  detection (lineage kill + `platform.token.reuse_detected`), password
  grant provably banned, 15 min access TTL enforced.
- **P3a done (2026-10-02):** `oauth_client_profiles` (type, tenant
  binding, allowed grants/scopes, audience, TTL overrides, PKCE flag,
  tier); `platform:client:create` (secret shown once, hashed at rest;
  partner requires active tenant; pos_device refused — device grant
  stays off); per-client grant enforcement on the token route
  (league-shaped errors; profile-less clients rejected).
- **P3b done (2026-10-02):** dual-accept in `auth.any` — JWT-shaped
  bearers verify RS256 + expiry + live revocation + audience-client
  checks (`PassportJwtVerifier`, house-style hand-rolled); tenant
  authority stays on live membership (no tenant claim yet — P4).
- **Tracks:** P4 HMAC sunset with date (+ custom JWT claims: tenant, acr/sid).
## Resolved

(none yet)
