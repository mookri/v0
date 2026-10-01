# Onboarding Domain (stage 1: engine + tracks)

## Tracks (versioned, pinned)

`packages/partner/src/onboarding/tracks.ts`. Three definitions v1:
`pandit-v1` (SLA 48h), `partner-vendor-v1` + `partner-individual-v1`
(SLA 72h). Rule changes ship as new versions; applications pin theirs —
no mid-flight rule changes, same discipline as pricing `policyVersion`.

No track-specific `if` in UI/API code: divergence unresolved by the
track definition is rejected in review.

## Application lifecycle

`packages/partner/src/domain/partner-application.ts`:

```
DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED
          ↓              ↓
       WITHDRAWN    REJECTED → APPEALED → UNDER_REVIEW
                       ↓           ↓
                   WITHDRAWN   WITHDRAWN
```

- Submit-once preserved (`canSubmitApplication`); resubmission after
  rejection links via `supersedes`, never mutates history.
- `applicationTransitionTable()` + model-based tour test
  (`tests/partner/onboarding-tracks.test.ts`) cover every legal path and
  assert illegal transitions throw — new states are covered automatically.
- Suspension/activation stays on the partner lifecycle (eligibility
  already gates on ACTIVE); application approval feeds it, separate commit.

## Step engine

`packages/partner/src/onboarding/steps.ts`: per-step predicates over
draft evidence are the single source of truth for completeness. UI
renders from `missingSteps()` (the applicant checklist + submit gate);
empty = submittable. Unknown step ids throw (fail-closed on definition
drift).

## Stage 2 (done)

OTP port (`identity/ports/otp.ts`) + memory/fake adapters; two-phase
submit in `partner/application/onboarding-submit.ts` (validate → challenge
→ re-validate → verify → transition). Wrong/expired/exhausted/replayed
codes collapse to `OTP_INVALID` (no oracle); rate limits enforced;
submit policy versioned (`SUBMIT_OTP_POLICY`).

Boundary note: the capability owns its port (`partner/ports/challenges.ts`);
identity's adapters satisfy it structurally — packages stay
self-contained per dependency rule 13 (this was enforced, not chosen:
the first draft imported identity source and failed `rootDir`).

## Stage 3 (done)

`OnboardingRepository` port + `MemoryOnboardingRepository` (save/findById/
findByIdentity/update; defensive copies; duplicate + missing guards;
supersedes linkage preserved). Shared store swaps behind the port later.

## Stage 4 (done)

Document metadata (`onboarding/documents.ts`) + `DocumentStore` /
`UploadUrlPort` (partner-owned) + memory adapters + pure worker logic
(`verify-document.ts`: MIME allowlist per kind, 10MB cap, hash equality,
expiry; every rejection reason-coded). Uploads addressed
`onboarding/<app>/<kind>/<sha>` (content-addressed); scoped 15-min URLs.

Known debt: two document models coexist (`verification.DocumentStatus`
vs `OnboardingDocumentStatus`) — unify when the verification worker binds
a queue (stage 6); the onboarding model carries reason codes the old one
lacks.

## Stage 5 (done)

API v1 on the partner app (BFF standard): start / get / step-PUT /
submit+confirm (two-phase OTP) / documents / status, with ownership
checks, stable codes (`ONBOARDING_*`), rate limits. Contract tests drive
the full HTTP flow per track. Docs: `docs/api/pandit-onboarding.md` +
`docs/api/partner-onboarding.md`.

## Stage 6 (done)

Dynamic step UI on the partner app: `/onboarding/[id]` overview +
`/onboarding/[id]/[step]` renderer (one `StepForm` from `STEP_FIELDS`
layouts; per-step PUT with idempotency keys; `needs[]` checklist).
en+hi strings with parity test; locale via `?hl=hi` (same route, never a
separate deployment). Round-trip test proves assembled form values pass
the domain submit gate for all three tracks.

Gap note: field layouts are UI-owned data (stage 8 track-studio moves
them into versioned track data); documents step collects identifiers —
byte upload binds when storage does.

## Stage 7 (done)

Reviewer backend: `reviewApplication` (approve/reject w/ mandatory
reason/request-info w/ appeal guardrails) + append-only audit store +
per-track SLA evaluation. Routes: role-gated queue (states + SLA breach
flags) and decisions with four-eyes countersign on rejections (recorded
in the audit note). Appeal admission stays applicant-side; direct
approve-from-appeal is illegal (re-enter review).

## Stage 8+ (not started)

OTP port + two-phase submit · `OnboardingRepository` port + memory
adapter · document pipeline + verification worker · API v1 + `docs/api`
trio · dynamic step UI + i18n · reviewer console + audit + SLA timers ·
activation wiring · staging proof in `cutover-log.md`.
