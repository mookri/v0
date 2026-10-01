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

## Stage 2+ (not started)

OTP port + two-phase submit · `OnboardingRepository` port + memory
adapter · document pipeline + verification worker · API v1 + `docs/api`
trio · dynamic step UI + i18n · reviewer console + audit + SLA timers ·
activation wiring · staging proof in `cutover-log.md`.
