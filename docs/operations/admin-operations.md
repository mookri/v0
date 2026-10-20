# Admin Operations (phase 28)

## Ownership

- `/admin/**` is owned by `admin`, class `administrative`
  (`docs/architecture/route-ownership.yaml`). It stays on web — it is
  explicitly excluded from the strangler (see `cutover-runbook.md`).
- No `packages/admin` exists and none is planned: admin screens compose
  existing capabilities (booking support states, pandit verification,
  refunds, incidents) instead of duplicating their logic.

## Access model (target)

- Every `/admin/**` route requires an authenticated session with the
  `Admin` role. Checks live in Node (layouts/route handlers) — never in
  edge middleware (see `edge-session.md`).
- Resolution goes through the identity `ActorResolver` port (phase 24);
  the session-backed implementation replaces staging adapters without
  touching admin screens.
- Fail closed: unknown role → 403 envelope, never a redirect loop.

## Audit requirements

- Admin mutations (refunds, verification decisions, suspensions, content
  changes) must append to an audit trail: actor, action, target, reason,
  timestamp. No silent state changes from admin screens.
- Reads of PII (customer profiles, partner documents) are audit-logged.

## Gap register (blocks nothing in flight; blocks admin-adjacent cutover)

1. **No server-side role enforcement found** on the current admin
   workspace (`apps/web/app/admin` renders by role config, no session
   check). Before any admin-adjacent traffic moves, layouts must enforce
   the `Admin` role via `ActorResolver` or the routes stay internal-only.
2. Audit trail has no store yet — reuse the outbox pattern
   (`workers/outbox`) when the first audited mutation lands.
3. No admin health probe — add `/api/health`-equivalent if admin ever
   gets its own origin (not planned).

## Stance

Admin is deliberately last: it moves only after customer + partner
cutovers are stable and the session-backed `ActorResolver` exists.
Until then: web-only, role-gated, audited.
