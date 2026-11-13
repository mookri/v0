# ADR-0060: Arrival Evidence Schema & Human-Verified `NO_SHOW` Confirmation

## Status
Accepted (2026-11-11)

## Context
Before v1, `Booking` lacked structured arrival evidence (`TS-01`/`TS-02`), making punctuality scoring, tardiness attribution, and `NO_SHOW` incidents unverifiable and risking automated false no-show penalties on panchas/pandits.

## Decision
Introduce `pandit_arrivals` backed by explicit GPS/check-in evidence, `arrival_outcome` (`ON_TIME`, `LATE_GRACE`, `LATE_BREACH`, `NO_SHOW_UNVERIFIED`, `NO_SHOW_CONFIRMED`, `UNKNOWN`), and an operator-confirmed `ConfirmBookingNoShowUseCase`. `ScanMissingArrivalsCommand` flags candidates without auto-confirming `NO_SHOW`.

