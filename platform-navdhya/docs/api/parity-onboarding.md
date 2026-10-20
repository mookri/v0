# Onboarding Contract Parity — Next.js BFF ↔ Laravel module

Both surfaces implement the same flow with their platform's envelope and
code registry. This table is the contract: either side changing a row
updates this doc and both test suites in the same change.

## Flow parity

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| start | `POST /api/onboarding/start` → 201 `{applicationId, trackVersion}` | `POST /api/v1/onboarding/applications` → 201 `{id, track_version}` | shape differs by platform convention (camel vs snake); semantics equal |
| draft read | `GET /:id` → `needs[]` | `GET /:id/status` → `needs` via missing steps | equal (Laravel computes needs in status, not draft read) |
| step save | `PUT /:id/step/:s` → `{saved:true}` | same path/shape | equal |
| submit | `POST /:id/submit` → `{challengeId}` | same | equal |
| confirm | `POST /:id/submit/confirm` → SUBMITTED | same | equal |
| documents | `POST /:id/documents` → `{documentId, url}` | `POST /:id/documents` → `{id}` (bytes inline; URL flow lands with object storage) | **diverged by design** — converge on signed-URL flow later |
| review queue | partner BFF | `GET review/queue` + SLA flags, cursor pages (`limit`/`cursor`, `meta.page`), `?status=` within review states | Laravel superset (SLA flags + pagination) |
| decision | partner BFF accept/approve | `:approve`/`:reject`/`:request-info` verbs | equal semantics, finer routes |

## Code correspondence (stable)

| Situation | Next.js code | Laravel code |
|---|---|---|
| unknown application | `ASSIGNMENT_NOT_FOUND`→`ONBOARDING_NOT_FOUND` (404) | `not_found` (404) |
| foreign owner | `ASSIGNMENT_FORBIDDEN` (403) | `permission_missing` (403) |
| incomplete submit | `ONBOARDING_INCOMPLETE` (422) | `validation_failed` (422) |
| bad state | `ONBOARDING_INVALID_TRANSITION` (409) | `state_forbidden` (409) |
| bad OTP | `UNAUTHENTICATED` (401) | `unauthenticated` (401) |
| rate limited | `RATE_LIMITED` (429) | `rate_limited` (429) |

Codes differ by registry (BFF domain codes vs kernel snake codes) — clients
MUST branch on documented per-surface codes, never assume string equality
across surfaces. HTTP statuses are identical everywhere: that is the
cross-surface contract.
