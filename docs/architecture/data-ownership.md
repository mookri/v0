# Data Ownership Registry (§6.45, §5.16)

Single physical PostgreSQL today; logical ownership enforced now.

| Capability | Owns (mutation) | Tables |
|------------|-----------------|--------|
| user | users, profiles | users, profiles, preferences |
| puja | pujas, categories, rituals | pujas, categories, rituals, puja_categories, puja_variants |
| pandit | pandits, verification, expertise | pandits, pandit_expertise, pandit_verification |
| availability | slots, schedules, capacity | availability_slots, schedules, capacity |
| pricing | prices, rules, discounts | prices, pricing_rules, discounts |
| booking | bookings, lifecycle | bookings, booking_items, booking_status_history |
| search | none (read-only orchestration) | — |
| recommendations | none (stateless rules) | — |

Rules:
- One capability owns mutation of its tables. Others read via public contracts (`PujaSummary`, `PanditSummary`, `PriceQuote`) or projections — never direct cross-capability JOIN writes.
- Only `infrastructure/*/adapters` touch the DB driver. `packages/*/domain|application|contracts|ports` must not import DB clients, SQL, or ORM.
- Cross-capability reads: use capability contracts first; denormalized projections (`booking_projection`) only via events, with authoritative ownership unchanged.
- Source: `docs/architecture/capability-ownership.yaml`.
