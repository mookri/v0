# ADR-0035 — Panchang Ayanamsa Rectification & Stored Anchor Migration

Status: Accepted (2026-11-10)
Extends: ADR-0001, ADR-0011
Related: E-SC-01 (Task T-SC01-02, T-SC01-06), P-01, P-02, P-04

## Context

Prior to E-SC-01, `PanchangCalculator` evaluated Nakshatras and Lunar Months directly from tropical solar and lunar coordinates without applying the Chitrapaksha (Lahiri) Ayanamsa ($A \approx 24.21^{\circ}$ for 2026).
This defect caused:
1. **P-01**: A constant $+2$ mansion shift in Nakshatra calculations.
2. **P-02**: A $+1$ month shift in Masa determination for $\approx 80.7\%$ of the year.
3. **P-04 Invariance**: Tithi calculations remained mathematically sound because elongation $(\lambda_{moon} - \lambda_{sun})$ is invariant under any global Ayanamsa translation.

However, existing active `customer_ritual_reminders` stored anchor rules referencing the defective tropical Masa names. Silently switching the evaluation engine to sidereal coordinates would cause devotees' reminders (e.g. annual Shraddha or lunar birthdays) to jump by approximately 30 days without notice or explanation.

## Decision

1. **Ayanamsa Rectification**:
   - All sidereal lunar mansions and solar rashis are computed via Chitrapaksha/Lahiri Ayanamsa:
     $$A(t) = 23.858072^{\circ} + 0.0139697^{\circ} \cdot T + 0.0003086^{\circ} \cdot T^2$$
   - Elongation is preserved as strictly Ayanamsa-invariant.

2. **Anchor Versioning (`rules_version`)**:
   - `rules_version: 1`: Legacy rules stored prior to E-SC-01. When evaluated, these are recognized as legacy tropical-referenced records and evaluated using the v1 compatibility layer or surfaced with an upgrade notification prompt.
   - `rules_version: 2`: Authoritative sidereal rules created after E-SC-01 rectification.
   - Newly created reminders automatically receive `rules_version: 2`.

3. **Data Integrity & Non-Destructive Migration**:
   - A database migration stamps `rules_version: 1` onto pre-existing active reminders whose `anchor_rule` lacks a `rules_version` key.
   - Reminders are not silently mutated; versioned dispatch preserves predictability and customer trust.

## Tests

- `tests/Unit/Calendar/AyanamsaParityTest.php` validates mathematical rectification.
- `tests/Modules/Customers/StoredAnchorMigrationTest.php` validates versioned rule execution.

