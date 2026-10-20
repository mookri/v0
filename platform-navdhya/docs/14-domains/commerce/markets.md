# Markets Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [Shopify parity extensions §2](./markets.md)

Market definitions with the normative market price resolution algorithm (base price × FX × rounding — markets are a projection and never persist a price of record), duties at checkout, and tax display modes ([Shopify parity extensions §2](./markets.md)). Module code `markets`. Base price lists live in the pricing domain; checkout consumes markets via `MarketPriceQuery` / `MarketDutyQuery`.

---

## Module: Markets (module code `markets`)

### 2.1 Bounded context

A **market** is a sellable region segment (e.g. "India retail",
"US wholesale", "EU") that composes: which stores/channels it serves, its
currency, its duty rules, and its tax-display mode. Markets let one
catalog be sold with per-region pricing, currency, landed cost, and tax
presentation without duplicating products.

### 2.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `markets` | id, code (UQ per tenant), name, currency (CHAR(3)), status (`active|paused`), priority, countries JSON, tax_display (`inclusive|exclusive`), rounding_rule JSON, duty_mode (`none|at_checkout|deferred`) | soft delete |
| `market_channels` | market_id, channel_id (store sales_channels, [commerce domains §1.2](./store-checkout.md)) | which surfaces render this market |
| `market_price_rules` | id, market_id, kind (`fx_derived|fixed|multiplier`), variant_id NULL (NULL = market-wide), value JSON, valid_from/to | overrides FX-derived pricing per variant or market-wide |
| `market_duty_rates` | id, market_id, hsn_sac (catalog tax code), rate_pct, method (`ad_valorem|specific`) | per-market duty schedule |
| `market_price_projections` | market_id, variant_id, price (Money), fx_rate_used, computed_at | **eventual read model**; never the price of record |

### 2.3 Market price resolution (normative)

`MarketPriceResolver.resolve(market_id, variant_id, qty, date)`:

1. Start from base price via `PriceResolver` (pricing contract, [commercial core domains §4.3](../sales/pricing.md))
   in the **store base currency**.
2. If a `market_price_rules` row matches (variant-specific wins over
   market-wide), apply it: `fixed` replaces, `multiplier` scales.
3. Otherwise convert base → market currency using the finance FX rate
   effective on `date` ([finance domains §1.5](../finance/gl.md) `fx_rates`); missing rate ⇒ 422
   `markets_fx_rate_missing` (no silent fallback).
4. Apply `rounding_rule` (e.g. charm-round to `.99`, round to nearest
   minor unit) — Money math only ([tenancy & data §9](../../13-data/data-architecture.md)).
5. Return `{price, currency, source, fx_rate_used}`; persist to
   `market_price_projections` asynchronously (cache), never read back as
   authoritative in the same request.

`MarketPriceResolver` is a contract: `Markets\Contracts\MarketPriceQuery`,
consumed by checkout ([commerce domains §2.4](./store-checkout.md) step 1) and the storefront public API
([commerce domains §1.5](./store-checkout.md)) when a market is in scope.

### 2.4 Duties at checkout (normative)

When `duty_mode = at_checkout` and the shipping destination country ∈
market `countries`:

1. Checkout pipeline ([commerce domains §2.4](./store-checkout.md)) inserts a **duty step after tax**
   (step 4.5): for each line, resolve `market_duty_rates` by the variant's
   `hsn_sac` (catalog, [commercial core domains §1.2](./catalog.md)); compute `ad_valorem` (rate × line
   value) or `specific` (rate × qty).
2. Duty is recorded as a separate `checkout_calculations` component
   `duty_total` and appears on the order as a non-discountable line
   (discounts never apply to duty).
3. `duty_mode = deferred` records duty as an estimate only (shown, not
   charged); `none` skips.
4. Duty amounts post to a `duty_payable` account via the tax engine's
   account mapping ([finance domains §2](../finance/tax.md)), keeping the ledger jurisdiction-clean.

### 2.5 Tax display mode (normative)

`tax_display` controls **presentation only**, never computation:

- `inclusive`: buyer-facing prices include computed tax; checkout shows
  "tax included" breakdown.
- `exclusive`: prices shown net; tax added and itemized at checkout.

The tax engine ([finance domains §2](../finance/tax.md)) always computes the same amount; the market's
display mode determines whether the storefront/checkout add it into the
displayed unit price. Admin/merchant surfaces always show both.

### 2.6 Primary flows

1. **Market creation**: define market → currency + countries + display →
   attach channels → `markets.market.created`.
2. **Price projection refresh**: on `pricing.*`, `catalog.*`, or
   `gl.fx_rate.updated` events, recompute affected
   `market_price_projections` (batched job); staleness surfaced via
   `computed_at`.
3. **Duty schedule upload**: bulk import of `market_duty_rates` via §14.

### 2.7 Event catalog

`markets.market.created` / `.updated` / `.paused` / `.activated`,
`markets.price_rule.updated`, `markets.duty_rate.updated`,
`markets.projection.stale` (market_id, variant_id).

### 2.8 API surface (`/api/v1/markets`)

CRUD markets/rules/duties under `markets:market:view|create|update`,
`markets:price_rule:view|create|update`, `markets:duty:view|configure`
(configure sensitive); `POST /markets/{id}:pause` / `:activate` under
`markets:market:update`; `POST /markets/resolve` (dry-run market price)
under `markets:market:view`. Scopes: `markets:read`, `markets:write`,
fine `markets:duty:configure`.

Contracts published: `MarketPriceQuery`, `MarketDutyQuery` (checkout).

### 2.9 Integration points

Consumes: pricing (`PriceResolver`), finance (FX `fx_rates`), catalog
(`hsn_sac`), store (channels), tax engine (duty posting).
Publishes to: checkout (price + duty steps), storefront public API,
analytics (market mix).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| markets.* | 10¹–10² (config) + projection jobs |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| markets | `markets:market:view|create|update`, `markets:price_rule:view|create|update`, `markets:duty:view|configure` (configure sensitive) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Market pricing determinism**: fixed FX table + rule set ⇒ identical
   market prices across repeated resolution; missing FX ⇒ 422
   `markets_fx_rate_missing`; projections never read back as authoritative
   in the resolving request.
2. **Duty correctness**: ad-valorem and specific duty fixtures produce
   exact duty totals; discounts never reduce duty; duty posts to the
   mapped ledger account.
3. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
4. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
