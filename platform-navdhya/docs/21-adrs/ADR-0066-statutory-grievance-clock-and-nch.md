# ADR-0066: Statutory Grievance Clock Separation & Manual NCH Fallback

## Status
Accepted (2026-11-11)

## Context
Treating statutory Indian consumer grievances as ordinary support tickets risked calendar arithmetic drift (e.g. Jan 31 -> Feb 28/29), missing verbatim complaint archives, or premature closure without an officer decision (`TS-04`/`TS-05`/`TS-06`).

## Decision
Enforce statutory grievance clocks via `StatutoryClockService` (`48h` acknowledgement, deterministic calendar-month resolution clamping), archive immutable JSON complaint copies for devotee download, block grievance closure unless an `officer_decision` exists, expose config-driven public Grievance Officer details without auth or personal emails, and track manual NCH (`1915`) referrals.

