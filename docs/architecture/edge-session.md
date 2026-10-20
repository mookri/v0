# Edge / Session Decision (ADR)

Status: accepted. Date: 2026-10-01.

## Decision

Session and auth logic runs in the **Node.js runtime only**. Edge middleware
is limited to static concerns: path redirects/rewrites and security headers.
No session reads, no signing, no store access at the edge.

## Context

- `packages/identity` sessions/handoffs resolve via server-side stores
  (`MemoryHandoffStore` today, DB-backed later). Edge has no access to them.
- `packages/config` `validateEnv()` is explicitly server-only; session signing
  keys must never ship to the edge bundle.
- Customer BFF (`app.navdhya.com/api/*`) composes use cases server-side;
  route handlers already run in Node.

## Consequences

- `middleware.ts` (if added) MUST NOT import `@navdhya/identity`,
  `@navdhya/contracts` session types, or any `infrastructure/*` store.
  An architecture gate SHOULD be added when the first middleware lands.
- Redirects that need identity (e.g. `/book` intent handoff, phase 21) live
  in Node route handlers/layouts, not middleware.
- Revisit when DB-backed sessions + edge-safe verification exist
  (tracked under phases 24/25). Reversal: move verification into middleware
  behind the same port interface — no browser changes required.
