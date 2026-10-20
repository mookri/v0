# Layout Engine

Framework-neutral presentation decision runtime. Answers **how** to present — never what the business truth is.

Owner: presentation platform

- Core: `LayoutContext` (product/route/viewport/actor/locale/experience/experiment), `LayoutDecision` (shell/layout/regions identifiers only — never React components)
- Policy: declarative `{ when, result, priority }`, precedence explicit-route 1000 > product 800 > experience 600 > viewport 400 > default 0
- Registry: string-keyed shell/layout/region registries; app adapters map ids to React components
- Resolver: two-stage `resolveLayoutContext` → `resolveLayout` (deterministic, serializable)
- Experiment: consumes `ExperimentAssignment`; no hashing in core (edge-safe, no node:crypto)
- Telemetry: `layout_decision` event with policy id + version (`2026.10.01`)

Dependencies: none (foundation-agnostic by design; app layer may combine with `@navdhya/contracts/config/routing/observability`).
Does not own: auth, booking, pricing, DB, Redis, Cloudflare, SEO rules.
Viewport (`mobile`) never selects product; auth never auto-selects app (§7.50).
