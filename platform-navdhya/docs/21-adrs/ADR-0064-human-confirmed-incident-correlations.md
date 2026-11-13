# ADR-0064: Human-Confirmed Scored Incident Correlations

## Status
Accepted (2026-11-11)

## Context
Auto-merging overlapping bookings into a single incident silently hid individual devotee losses and risked conflating distinct ceremony failures (`TS-12`).

## Decision
Score candidate correlations by shared pandit, venue, stream room, and muhurat window overlap via `IncidentCorrelationService` in `SUGGESTED` state. Confirmation and revocation require an explicit human commander action (`confirmCorrelation` / `revokeCorrelation`) with zero automatic case merging.

