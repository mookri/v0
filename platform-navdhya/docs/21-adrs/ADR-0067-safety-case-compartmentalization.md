# ADR-0067: Safety Case Compartmentalization & No-Mediation Policy

## Status
Accepted (2026-11-11)

## Context
Safety, harassment, and threat reports appearing in generic support queues, receiving automated post-ceremony rating prompts, or being resolved with apologies/store credit constituted severe trust and safety failures (`TS-14`/`TS-15`/`TS-16`).

## Decision
Restrict `SCOPE_RESTRICTED_SAFETY` cases behind `support:safety_case:access`, encrypt `REPORTER_PRIVATE` communications at rest via `Crypt::encryptString`, expose public `112` emergency signposting, suppress feedback invitations via `support.safety_case.escalated`, forbid mediation remedies (`safety_no_mediation_forbidden`) and informal PIPs (`safety_pip_forbidden`), and require time-boxed (`<= 30d`) Safety Lead approval for `IncidentInterimRestriction`.

