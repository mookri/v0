# ADR-0065: Gate-Verified & Devotee-Consented Replacement Recovery

## Status
Accepted (2026-11-11)

## Context
Silently swapping a pandit during an emergency disruption risked breaching tradition matching, slot availability, interim safety restrictions, or devotee consent (`TS-13`).

## Decision
Evaluate replacement candidates through `ReplacementEligibilityEvaluator` (`FULL_GATES` / `PARTIAL_GATES`, excluding restricted or concurrently held pandits), create atomic `RESERVED_HOLD` records, require explicit devotee consent (`devotee_consent_required`) before invoking `ManagesPanditAssignments::reassignForIncident`, and fail cleanly to `RESCHEDULE_OR_FULL_REFUND` when no eligible candidate exists.

