# ADR-0042: Four-Mode Evidence Model & Mode Ceiling Honesty

## Status
Accepted (2026-11-11)

## Context
Offline religious institutions and unconfirmed issuers frequently cannot participate in real-time registry checks.

## Decision
Define four distinct modes in `EvidenceMode` (`native`, `manual`, `official`, `financial`) with `QualificationModeCeilingPolicy` preventing `official` mode unless an issuer holds `authoritative` grade and a confirmed response record, while routing offline non-responses cleanly to `manual` without punitive rejection.

