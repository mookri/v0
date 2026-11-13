# ADR-0046: Encrypted Fiscal Storage, Structural GSTIN & Payout Ownership Invariant

## Status
Accepted (2026-11-11)

## Context
Raw GSTIN and bank numbers previously resided in `partner_applications.evidence` JSON, length-only GST regex blocked legitimate non-GST small vendors (`T-09`), and disbursements lacked ownership checks.

## Decision
Store encrypted credentials in `verification_tax_registrations` and `verification_bank_instruments`, validate GSTINs structurally with per-position reason codes plus non-GST exempt tracks, keep `NameMatcher` strictly advisory (reusing Search `TransliterationSchemes`), and enforce `VerifiesPayoutOwnership` in `Finance`.

