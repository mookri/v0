# ADR-0043: Dual Control Segregation, Evidence-Set Hash & T2 OTP Quorum

## Status
Accepted (2026-11-11)

## Context
Single reviewers could trigger final decisions via empty client-side strings or without secondary review and quorum confirmation.

## Decision
Enforce applicant/reviewer segregation and evidence-set SHA-256 drift cancellation inside `DecisionService`, fail-closed API validation without empty countersign fields, and mandatory two-channel quorum in `OtpChallengeManager::verifyQuorum()` for T2 onboarding decisions.

