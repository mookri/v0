# ADR-0045: Institutional Existence vs. Representative Authority Graph

## Status
Accepted (2026-11-11)

## Context
Submitting a valid temple trust deed alone was insufficient to guard against unauthorized representative takeover or competing trustee claims.

## Decision
Separate `institutional_existence` from `institutional_authority` inside `TempleAuthorityService` and `verification_authority_grants`, block approval or bank mandate attachment without valid unexpired authority (§7.2), escalate competing claims (`CONFLICTING_TRUSTEE_CLAIM`), and apply dual-review plus 48h cooling-off on mandate updates.

