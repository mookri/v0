# ADR-0071: Optimistic-Locking Assignment Transitions & Start Verb

## Status
Accepted (2026-11-11)

## Context
Pandit ritual dispatch requires conflict-safe confirmation, explicit ceremony start evidence (`start` verb + arrival/samagri checklist), and human-readable conflict guidance when overlapping bookings or travel buffers refuse acceptance (`A-06`, `A-07`).

## Decision
Keep state transitions in Laravel `PanditAssignmentService` with optimistic concurrency/locking and travel-buffer checks; expose `/api/pandit/assignments/{id}/start` cleanly via `@navdhya/partner-app`, enforce online-only execution, and surface clear human-readable messages via `describeAssignmentError()`.

