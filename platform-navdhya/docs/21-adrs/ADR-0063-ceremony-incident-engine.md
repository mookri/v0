# ADR-0063: Ceremony Incident Engine & Closure Preconditions (`SEV_0`–`SEV_4`)

## Status
Accepted (2026-11-11)

## Context
Generic infrastructure incidents lacked ceremony-specific taxonomy (`SEV_0`, `NO_SHOW`, `STREAMING_DISRUPTION`, `TEMPLE_ACCESS_DENIAL`), attributed cause isolation, and objective postmortem/prevention closure gates (`TS-11`).

## Decision
Extend `packages/incident` and `App\Modules\Incident` with 12 ceremony-aware tables, explicit `AttributedCause` (`PROVIDER`, `VENUE`, `PLATFORM`, `DEVOTEE`, `EXTERNAL`, `UNKNOWN`), append-only `incident_severity_history` downgrades, and fail-closed `CLOSED` preconditions requiring summary, cause attribution, postmortem (`SEV_0`/`SEV_1`), and prevention tasks. Non-provider causes (`VENUE`/`PLATFORM`/`EXTERNAL`) are excluded from pandit quality penalties.

