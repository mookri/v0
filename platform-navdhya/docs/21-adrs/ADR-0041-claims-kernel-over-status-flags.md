# ADR-0041: Scoped Evidence-Backed Claims Kernel Over Single Verification Booleans

## Status
Accepted (2026-11-11)

## Context
Previously, `PanditProfile`, `Temple`, and `Vendor` relied on direct writes to `verification_status`, allowing a single boolean-style column to misrepresent self-declared or legacy records as officially verified.

## Decision
Introduce `App\Domain\Verification\Claims\ClaimManager` (`verification_claims`, `verification_claim_scopes`, `verification_evidence`) as the single authoritative store for verification assertions, protected by `BlocksDirectVerificationStatusWrite` on partner models and synchronized strictly through `VerificationProjector`.

