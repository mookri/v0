# Extraction Readiness (L0–L6)

Date: 2026-10-01. Evidence: `node tooling/scripts/readiness-evidence.js`
(45 packages) + arch gates (30 files / 84 passing) + full suite
(57 files / 228 passing). Re-score after every boundary change.

## Rubric

| Level | Meaning | Gate |
|-------|---------|------|
| L0 | No boundary — flat files, no layers | — |
| L1 | Layered layout (`domain` and/or `application`) | arch: domain free of `apps/` |
| L2 | Ports defined (dependency inversion; infra swappable) | `ports/` present |
| L3 | Contracts published (public index + `contracts/`) | `contracts/` + index |
| L4 | Adapter exists (local adapter or infra adapter bound to the port) | adapter in package or `infrastructure/` |
| L5 | Proven pure (L4 + no React/Next/`@apps` deps + gates green) | arch + unit green |
| L6 | Independently deployable service | out of scope — `services/` empty by decision |

Notes:

- Intra-package `@navdhya/<pkg>/src/…` specifiers are the codemod's absolute
  form of same-package imports, NOT cross-package coupling (the codemod
  aborts on true cross-package imports; violations=0).
- `ui/` layers in `ads`/`booking` are pure functions (no React import) —
  they do not break purity.
- L6 is intentionally unreached: no extraction is justified yet; the first
  candidate would be the highest-churn L5 capability behind a stable port.

## Scores

| Package | Level | Rationale / blocker |
|---------|-------|---------------------|
| availability | L5 | domain/application/contracts/ports/adapters, clean |
| booking | L5 | +events, intent handoff contract; pure `ui/` helpers |
| pandit | L5 | full layers + adapters, clean |
| pricing | L5 | full layers + adapters, clean |
| puja | L5 | full layers + adapters, clean |
| recommendations | L5 | full layers + adapters, clean |
| search | L5 | port bound in `infrastructure/search`, clean |
| user | L5 | full layers + adapters, clean |
| identity | L4 | ports+adapters incl. ActorResolver port, no `contracts/` dir — blocker: publish session/handoff contracts |
| content | L3 | contracts published, no adapter — blocker: content source adapter |
| payment | L5 | HMAC webhook auth + verified-gate, Razorpay adapter bound, clean |
| ads | L4 | domain/application/contracts/ports, pure `ui/`; adapter externalized — confirm before L5 |
| communication | L4 | domain/ports + adapters, clean |
| customer-profile | L3 | domain/application/ports, no contracts — blocker: publish 360 contracts |
| finance | L2 | domain/application/ports, no contracts — blocker: invoice/ledger contracts |
| fulfillment | L2 | domain/ports, no application/contracts split — blocker |
| geography | L2 | domain/ports, no application/contracts split — blocker |
| feedback | L2 | domain/application, no ports — blocker: review/moderation ports |
| incident | L2 | domain/application, no ports — blocker |
| notifications | L4 | live events + bus port + memory adapter, clean |
| partner | L2 | domain/application, no ports — blocker: lifecycle ports (see 27-partner-migration) |
| resource-management | L2 | domain/application, no ports — blocker |
| support | L2 | domain/application, no ports — blocker |
| commerce | L1 | domain/application only — blocker: ports + contracts |
| layout-engine | L1 | flat engine, cross-refs internal — blocker: ports |
| seo | L1 | contracts only — derivation layer, likely never extracts |
| api-contracts | L1 | shared types surface — stays shared by design |
| config | L1 | foundation — stays shared by design |
| i18n | L1 | foundation — stays shared by design |
| observability | L1 | foundation — stays shared by design |
| routing | L1 | foundation — stays shared by design |
| security | L1 | foundation — stays shared by design |
| design-system | L1 | React by definition — never extracts; UI-DEP expected |
| aeo | L0 | 2 files, no layers — blocker: define boundary or fold into seo/content |
| analytics | L0 | 1 file — blocker: ports + sink adapter |
| api-client | L0 | 1 file — blocker: define client port surface |
| attribution | L0 | 1 file — blocker: fold into analytics or define boundary |
| authorization | L0 | 1 file — blocker: policy ports (vs identity) |
| discovery | L0 | 1 file — composition over search/recommendations; define or fold |
| events | L0 | 3 files — shared envelope; stays shared by design |
| experimentation | L0 | 1 file — blocker: variant ports |
| personalization | L0 | 1 file — blocker: fold into recommendations or define boundary |
| scheduling | L0 | 3 files — blocker: define boundary vs availability |

## Extraction order (when justified)

1. L5 group first (booking/pricing/payment carry money — need hardening first).
2. Promote L2/L3 blockers only on demand (a route or app needs the seam).
3. L0 thin packages: fold or define — no new package without a consumer.
