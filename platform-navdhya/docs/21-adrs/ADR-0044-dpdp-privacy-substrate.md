# ADR-0044: Purpose-Scoped Non-Cascading DPDP Privacy Substrate

## Status
Accepted (2026-11-11)

## Context
Ad-hoc consent checks lacked revocation isolation, residency gating, retention sweeps, and DSR erasure orchestration.

## Decision
Centralize consent in `App\Domain\Privacy` (`ConsentManager`, `ConsentPurpose`, `ProviderResidencyGate`, `PurgeExpiredRetentionCommand`, `ProcessPartnerErasureJob`) requiring explicit per-purpose consent and DPA/residency registration before external outreach or screening.

