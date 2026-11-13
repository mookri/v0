# ADR-0040 — Ritual Identity Attribute Classification & Erasure Scope

Status: Accepted (2026-11-10)
Extends: ADR-0005, ADR-0016
Related: E-SC-01 (Task T-SC01-09), P-17

## Context

Spiritual attributes (Gotra, Nakshatra, Rashi, Kuladevata, and family lineage/pitru records) represent special-category sensitive personal data under DPDP and GDPR. Prior to E-SC-01, these fields accepted unvalidated arbitrary strings in HTTP payloads (e.g. `nakshatra => 'shravna'`, `gotra => 'random_gotra'`), leading to data corruption and potential SQL/string injection risks. Furthermore, data retention and erasure guarantees required formalization between warehouse export exemptions and privacy deletion paths.

## Decision

1. **Canonical Enumerations**:
   - `Nakshatra`: Strictly validated against `NakshatraEnum` (the 27 classical Vedic lunar mansions: `ASHWINI` through `REVATI`). Typo'd or informal names are rejected with 422 Unprocessable Entity.
   - `Rashi`: Strictly validated against `RashiEnum` (the 12 sidereal solar rashis: `MESHA` through `MEENA`). Western zodiac names are rejected.
   - `Gotra`: Strictly validated against `GotraEnum` (the canonical Sapta/Ashta-Rishi lineages + classical gotras, with `OTHER` as the controlled escape hatch).
   - `Timezone`: Strictly validated against `DateTimeZone::listIdentifiers()`. Client-supplied arbitrary strings are rejected.

2. **Warehouse Exclusion**:
   - `WarehouseExportService` maintains a hard exclude list for all ritual identity attributes (`gotra`, `nakshatra`, `rashi`, `kuladevata`, `shraddha`, family members). Under no circumstances are spiritual identifiers exported to third-party data lakes or unconsented analytics pipelines.

3. **Devotee Right to Erasure**:
   - Devotee erasure requests (`POST /api/v1/customers/me/erasure`) systematically soft-delete `customer_spiritual_profiles` and `customer_family_members`, hard-delete `profile_field_provenance` and `customer_ritual_reminders`, and pseudonymize telemetry events (`user_id = null`), preserving only statutory financial compliance documents (invoices, bookings) as mandated by law.

## Tests

- `tests/Modules/Customers/RitualIdentityValidationTest.php` asserts 422 rejection for typos, acceptance for canonical symbols, and complete erasure execution.

