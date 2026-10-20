# Discovery Note Template (required before code)

Every slice opens with a note answering all eight. Rough numbers beat no
numbers; "unknown, will measure in staging" is acceptable once, never twice.

## 1. Who consumes it?

Human (which persona) / service / worker / browser / scheduler. Authentication
kind and the exact credential each consumer presents.

## 2. What breaks if it lies?

Failure class: money / identity / audit / UX / availability. The single
worst honest failure mode, stated plainly.

## 3. Where does it run?

Edge / Node / worker / browser / MySQL / Redis. Runtime limits that follow
(stateless only, no local disk, 30s ceiling, memory cap).

## 4. What data exists already?

Pre-era rows, legacy columns, NULLs, placeholder/test data in the path.
Backfill plan with commands named (not "later").

## 5. What is the traffic shape?

Reads:writes ratio, peak rpm, burst source, payload sizes. Scaling profile
(transactional / read-heavy / queue-oriented / worker-oriented / realtime).

## 6. What does it cost per unit?

SMS / storage-GB / compute-ms / reviewer-minutes per operation. Meter name
that will carry the number.

## 7. What is the rollback?

Flag / version pin / dual-run / backup restore. Who executes it and what
evidence they record (cutover-log entry shape).

## 8. What test proves the negative?

Replay / forgery / expiry / cross-tenant / double-submit / clock-skew case
by name. At least one adversarial test per slice, named here before code.
