# Intelligence Domain — Forecasting, Anomaly, Recommendations, Copilot & Agents

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [intelligence §2](./intelligence.md)–7; authorization register SEC-014, SEC-020…024

The intelligence modules on top of the analytics read path: demand & cash forecasting ([intelligence §2](./intelligence.md)), anomaly detection with seeded rules and blocking actions ([intelligence §3](./intelligence.md)), deterministic recommendations ([intelligence §4](./intelligence.md)), the AI copilot with a tool allowlist and budgets ([intelligence §5](./intelligence.md)), and the agent framework with human-in-the-loop approval ([intelligence §6](./intelligence.md)). All intelligence modules read via the [analytics](../16-analytics/analytics.md) connection only and never write to non-intelligence tables (safety invariants below). Module codes `forecasting`, `anomaly`, `recommendations`, `copilot`, `agents`.

---

## Module: Forecasting (module code `forecasting`)

### 2.1 Bounded context

Deterministic, explainable forecasts: **demand forecasting** (variant ×
warehouse weekly demand feeding supply chain) and **cash-flow forecasting**
(13-week rolling view from AR/AP/recurring). ML-grade model training is out
of v1 scope; v1 ships statistical baselines with a driver interface for
later ML models.

### 2.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `forecast_runs` | id, kind (`demand|cash`), horizon_weeks, method, status (`queued|running|completed|failed`), run_at, params JSON, accuracy_metrics JSON | |
| `demand_forecast_lines` | id, run_id, variant_id, warehouse_id, week_start, forecast_qty, components JSON (baseline, trend, seasonality, events) | explainability payload |
| `cash_forecast_lines` | id, run_id, week_start, expected_inflows JSON by source, expected_outflows JSON by source, net_position | |
| `forecast_backtests` | id, run kind, as_of, horizon, method, mape/wape metrics | accuracy tracking over time |

### 2.3 Demand forecast pipeline (normative)

1. Input: `SalesHistoryQuery` (analytics contract) + open orders
   (sales contract) + promotions calendar (discounts events) + lead times
   (suppliers contract).
2. Method chain (per variant, selected by data sufficiency):
   - ≥ 104 weeks history → seasonal decomposition (weekly seasonality +
     trend);
   - 26–103 weeks → Holt-Winters additive (damped);
   - 8–25 weeks → 4-week weighted moving average;
   - < 8 weeks → manual/zero baseline flagged `insufficient_history`.
3. Event overlays: known promotions uplift the baseline by the tenant's
   `forecasting.promo_uplift_default` (default +15%) in promo weeks;
   tenant overrides per promotion rule.
4. Output lines with `components` (baseline/trend/season/event) so the
   UI explains every number; run accuracy = backtest WAPE on the last
   completed horizon.
5. Consumer: supply chain replenishment (§[enterprise ops domains §6.3](../14-domains/supply-chain/supply-chain.md)) reads the latest
   completed run; a missing/failed run falls back to reorder-point logic
   (never blocks replenishment).

### 2.4 Cash forecast pipeline

1. Inflows: AR open items by due date (`ReceivablesAging` contract) +
   subscription renewals due (subscriptions contract) + forecast cash sales
   (demand forecast × average order value × payment mix).
2. Outflows: AP due items (`PayablesAging`) + payroll runs scheduled
   (payroll calendar) + recurring expense patterns (GL expense account
   history via analytics facts).
3. Output: 13-week rolling weekly net position + ending balance projection
   against current `CashPositionQuery` (banking contract).
4. Alerts: projected negative balance in any week →
   `forecasting.cash.shortfall_projected` (P2 notification to finance
   role).

### 2.5 Scheduling & ops

- Demand forecast: weekly (Sunday 02:00 tenant local), horizon 13 weeks;
  manual re-run allowed (`forecasting:run:execute`).
- Cash forecast: daily 04:00, horizon 13 weeks.
- Runs are tenant-scoped jobs ([tenancy & data §4.4](../06-tenancy/tenancy-and-data.md)); failures retry with backoff,
  then `forecasting.run.failed` ops event.

### 2.6 Event catalog

`forecasting.run.completed` (kind, horizon, accuracy),
`forecasting.run.failed`, `forecasting.cash.shortfall_projected`,
`forecasting.accuracy.degraded` (WAPE worsening > 10 pts vs trailing
average — ops alert).

### 2.7 API surface (/api/v1/forecasting)

| Endpoint | Permission |
|----------|-----------|
| GET /runs, GET /runs/{id}/lines (filters: variant, warehouse, week) | `forecasting:run:view` |
| POST /runs (manual) | `forecasting:run:execute` |
| GET /cash (latest projection) | `forecasting:cash:view` |
| GET /accuracy (backtests) | `forecasting:run:view` |

Scopes: `forecasting:read`, `forecasting:write`.

Contracts: consumes analytics, ar, ap, banking, subscriptions, suppliers
contracts (§2.3/2.4). Publishes: supply_chain consumes via
`forecasting:run:view` API (direction preserved — supply chain polls;
forecasting does not push).

---
---

## Module: Anomaly Detection (module code `anomaly`)

### 3.1 Bounded context

Unsupervised + rule-based signal detection over event streams and
projections: fraud indicators, operational anomalies, data-quality issues.
Anomalies produce **alerts and (optionally) workflow blocks** — they never
auto-mutate business state.

### 3.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `anomaly_rules` | id, code, name, kind (`threshold|velocity|deviation|pattern`), source (event name or fact), condition JSON, severity (`low|medium|high|critical`), action (`alert|alert_and_block`), status | tenant-tunable thresholds; platform defaults seeded |
| `anomaly_baselines` | id, rule_id, dimension key (tenant×org×metric), rolling stats JSON (mean, stddev, percentiles), updated_at | maintained by the baseline job |
| `anomalies` | id, rule_id, detected_at, severity, subject_type/id, evidence JSON (values vs baseline), status machine (`open|acknowledged|resolved|false_positive`), assigned_membership_id NULL | |
| `anomaly_feedback` | id, anomaly_id, verdict (`true|false_positive`), note, membership_id | tuning input |

### 3.3 Seeded detection rules (platform defaults)

| Code | Kind | Signal |
|------|------|--------|
| `anomaly.payment.velocity` | velocity | > N inbound payment captures for one customer within 10 min |
| `anomaly.refund.ratio` | threshold | tenant daily refund amount > X% of captured amount |
| `anomaly.discount.depth` | deviation | order discount % beyond 3σ of tenant's trailing distribution |
| `anomaly.stock.negative_drift` | pattern | repeated adjustments shrinking a variant's stock without receipts |
| `anomaly.login.geo` | pattern | login from a new country for a user within < 1 h of prior login (kernel auth events) |
| `anomaly.token.reuse` | pattern | refresh-token reuse detected (kernel security event passthrough — always critical) |
| `anomaly.price.tamper` | deviation | checkout unit price deviating from PriceResolver output (bypass attempt) |
| `anomaly.rounding.abuse` | pattern | rounding_adjustment lines exceeding frequency threshold |
| `anomaly.after_hours.sensitive` | threshold | sensitive operations (payouts, role grants) outside tenant business hours |
| `anomaly.sod.warning_cluster` | pattern | cluster of SoD warnings on one membership ([authorization & policy §7.4](../04-security/authorization-and-policy.md)) |

Rule codes are stable configuration identifiers, not event names: the
`anomaly.<signal>.<facet>` shape is reserved for rule codes; detections emit
the `anomaly.case.*` events cataloged in §3.5.

### 3.4 Detection pipeline (normative)

1. Stream listeners evaluate `threshold`/`velocity`/`pattern`
   rules inline on events (budget: ≤ 50 ms added per event; heavier rules
   run on 5-min micro-batches over projections).
2. `deviation` rules compare against `anomaly_baselines`
   (rolling 90-day stats per dimension; baseline job nightly).
3. On match: create `anomalies` row with evidence → severity routing:
   - `alert`: notification to the configured role queue + dashboard;
   - `alert_and_block`: additionally emits a kernel **guard signal**
     consumed by the workflow engine — the *next* transition of the same
     aggregate kind requires ACR 2 approval by a different membership
     (implemented as a dynamic approval policy, [kernel services §4.3](../11-workflow/workflow-engine.md)). v1 applies
     blocking only to `anomaly.payment.velocity` and
     `anomaly.price.tamper`.
4. Every detection emits `anomaly.case.detected` (carries rule code +
   subject refs; evidence values are operational data, not PII — personal
   data fields are referenced by id only).
5. Triage workflow: acknowledge → investigate (links to audit trail entries
   via correlation_id, [kernel services §5](../07-kernel/kernel-services.md)) → resolve or mark false_positive →
   feedback row feeds threshold tuning suggestions (manual in v1).

### 3.5 Event catalog

`anomaly.rule.created` / `.updated` / `.disabled`,
`anomaly.case.detected` (severity, rule, subject refs),
`anomaly.case.acknowledged` / `.resolved` / `.false_positive`,
`anomaly.block.enforced` (dynamic approval applied).

### 3.6 API surface (/api/v1/anomaly)

| Endpoint | Permission |
|----------|-----------|
| GET /anomalies (filters: severity, status, rule), PATCH (triage) | `anomaly:case:view|triage` |
| CRUD rules | `anomaly:rule:configure` (sensitive) |
| GET /baselines | `anomaly:rule:configure` |
| POST /anomalies/{id}/feedback | `anomaly:case:triage` |

Scopes: `anomaly:read`, `anomaly:write`.

### 3.7 Integration points

Consumes: kernel events (auth, SoD), domain events, analytics facts.
Publishes to: notifications, workflow engine (dynamic approvals), audit
(triage decisions are audited).

---
---

## Module: Recommendations (module code `recommendations`)

### 4.1 Bounded context

Contextual, explainable suggestions surfaced inside the admin console and
API: next-best actions, cross-sell/upsell, replenishment nudges, pricing
nudges, and dunning prioritization. Recommendations are **advisory** — they
never execute; a human (or an explicitly authorized agent, §6) acts on them
through the normal authorized APIs.

### 4.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `recommendation_models` | id, kind (`next_best_action|cross_sell|replenishment|pricing|dunning`), source, config JSON, status | per-kind config |
| `recommendations` | id, kind, subject_type/id (customer, order, variant, invoice), context JSON, score DECIMAL(8,4), rationale JSON (human-readable factors), status machine (`generated|shown|accepted|dismissed|expired`), expires_at | tenant-scoped |
| `recommendation_outcomes` | id, recommendation_id, outcome (`accepted|dismissed|converted`), occurred_at, ref (created doc id) | feedback loop |

### 4.3 Generators (v1, deterministic)

1. **Cross-sell**: co-purchase frequency over trailing 90 days of order
   lines (analytics facts) → for an open cart/order, suggest top-3 variants
   frequently bought with the current set, excluding already-present
   variants; rationale = "bought together N times".
2. **Replenishment nudge**: variants whose projected stock (on_hand −
   open demand inside lead time, [enterprise ops domains §6](../14-domains/supply-chain/supply-chain.md)) falls below safety stock →
   suggestion with suggested qty + preferred supplier; links to supply-chain
   suggestion converter.
3. **Pricing nudge**: variants with zero sales for 60 days but stock on
   hand and active listing → markdown candidate list (advisory only;
   applying requires `pricing:override:create`).
4. **Dunning prioritization**: overdue invoices ranked by (amount × age ×
   customer payment-history score) → collector queue ordering for AR.
5. **Next-best action**: rule-driven per dashboard context (e.g. "3 orders
   awaiting approval", "cash shortfall projected in week 6") aggregating
   pending workflow items + forecast alerts.

### 4.4 Rules (normative)

- Generation runs on schedules (cross-sell: on cart update, lazy-computed;
  others nightly) and stores recommendations with expiry (default 7 days).
- Every recommendation carries a machine- and human-readable
  `rationale`; no black-box output is shown in the UI.
- Acceptance tracking: "act" buttons invoke the owning module's API with
  the recommendation id as `X-Recommendation-Id` header; the owning
  module records it in `recommendation_outcomes` via event
  (`recommendations.outcome.recorded`).
- Recommendations MUST respect org scoping and never surface data the
  viewing membership cannot read (generator filters by actor at read time).

### 4.5 Event catalog

`recommendations.batch.generated` (kind, count — ops telemetry),
`recommendations.outcome.recorded` (kind, outcome),
`recommendations.model.updated`.

### 4.6 API surface (/api/v1/recommendations)

| Endpoint | Permission |
|----------|-----------|
| GET /recommendations (filters: kind, subject) | host-resource view permission (read-time filter) |
| POST /recommendations/{id}:dismiss | host-resource update permission |
| GET /models, PUT /models/{kind} | `recommendations:model:configure` (sensitive) |

Scopes: `recommendations:read`, `recommendations:write`.

### 4.7 Integration points

Consumes: analytics facts, forecasting outputs, ar/invoicing contracts,
inventory availability. Publishes to: notifications (digest surfacing),
owning modules via outcome events.

---
---

## Module: AI Copilot (module code `copilot`)

### 5.1 Bounded context

A conversational assistant embedded in the admin console that answers
questions over tenant data and performs actions **strictly through
authorized kernel/module APIs** (tool use). The copilot is an LLM-backed
orchestrator with hard guardrails: permission intersection, budgets,
allowlisted tools, and full audit of every tool call.

### 5.2 Architecture (normative)

```text
user prompt
   │
   ▼
CopilotOrchestrator
   ├── context builder: actor membership, tenant, org scope, ACR
   ├── tool registry (allowlist per actor's effective authority, [authorization & policy §5](../04-security/authorization-and-policy.md))
   ├── budget guard: tokens/cost + tool-call caps per session & per day
   ├── LLM provider driver (swappable; default provider config via Settings)
   ├── tool executor: calls internal APIs in-process with the ACTOR's
   │   credentials (never elevated); every call audited
   └── response composer: answer + citations (record links) + tool trace
```

1. **No privilege escalation**: the orchestrator executes tool calls under
   the requesting membership's identity and active tenant/org context.
   Effective authority = [authorization & policy §5](../04-security/authorization-and-policy.md) intersection, re-checked per tool call.
   A tool the actor cannot perform via UI/API is invisible to the model.
2. **Step-up inheritance**: sensitive tools require the session's ACR 2
   ([identity & token platform §6](../04-security/identity-and-token-platform.md)); if absent, the copilot returns the step-up requirement
   instead of executing.
3. **Data minimization**: tool results passed to the LLM are truncated to
   field allowlists per tool; bulk list tools return aggregates + ids, not
   full records; PII fields (hr/payroll sensitive) are excluded unless the
   actor holds `view_sensitive` AND the tenant setting
   `copilot.allow_sensitive_context` is on (default off).
4. **Provider boundary**: prompts leave the platform only via the provider
   driver; tenant setting `copilot.provider` selects it; a
   `copilot.redaction` list strips secrets before egress; entire
   feature is kill-switchable per tenant (`feature_flag module.copilot`).

### 5.3 Tool registry (v1 allowlist)

Tools map 1:1 to existing API operations (same permission codes):

| Tool | Backing operation | Permission |
|------|-------------------|-----------|
| `search_records` | kernel Search across allowed modules | per-module `view` |
| `get_record` | resource GET endpoints | `<module>:<resource>:view` |
| `list_orders` / `get_order` | sales API | `sales:order:view` |
| `kpi_summary` | analytics KPI contract | `analytics:kpi:view` |
| `forecast_cash` | forecasting read | `forecasting:cash:view` |
| `create_draft_order` | sales POST (draft only) | `sales:order:create` |
| `create_customer` | customers POST | `customers:customer:create` |
| `draft_journal` | gl POST draft entry (never approve) | `gl:journal:create` |
| `send_notification` | kernel Notifier (template-bound) | `kernel:notifications:send` (kernel permission, sensitive) |
| `explain_anomaly` | anomaly case read + audit links | `anomaly:case:view` |

Normative exclusions (v1): approvals, payouts, role grants, deletions,
settings changes, exports — the copilot can explain how to do these and
link the screen, but cannot execute them (tool registry architecture test).

### 5.4 Budgets & safety

- `copilot_budgets`: per-tenant daily token/cost ceiling and per-user
  session caps (plan-gated defaults); exhaustion → graceful "budget reached"
  response + `copilot.budget.exhausted`.
- Tool-call cap: 8 per turn, 40 per session; loops detected by repeated
  identical call signatures.
- Every turn persisted in `copilot_conversations` (messages, tool
  calls with inputs/outputs redacted per §5.2.3, cost, latency); retained
  per tenant data policy; visible to tenant admins under
  `copilot:session:view`.
- Hallucination guard: numeric answers about tenant data MUST cite the tool
  result they came from (composer enforces citation or refuses the claim);
  free-form policy/legal advice is out of scope (disclaimer injected).

### 5.5 Event catalog

`copilot.session.started` / `.ended`,
`copilot.tool.invoked` (tool, actor, subject refs, outcome — audit
stream), `copilot.budget.exhausted`, `copilot.provider.error_logged`,
`copilot.safety.blocked` (attempted out-of-allowlist or
policy-violating call).

### 5.6 API surface (/api/v1/copilot)

| Endpoint | Permission |
|----------|-----------|
| POST /conversations (start), POST /conversations/{id}/messages | `copilot:use` (per-user; feature-flag gated) |
| GET /conversations (own), GET /conversations/{id} | owner or `copilot:session:view` |
| GET /tools (visible tool list for actor) | `copilot:use` |
| PUT /settings (provider, budgets, sensitive-context toggle) | `copilot:settings:configure` (sensitive) |

Scopes: `copilot:use`, `copilot:read`, fine sensitive
`copilot:settings:configure`.

### 5.7 Integration points

Consumes: analytics (KPI), forecasting, anomaly, and every module whose
read/write APIs are tool-mapped (via in-process authorized calls).
Publishes to: audit (tool calls), usage meters (cost).

---
---

## Module: Agent Framework (module code `agents`)

### 6.1 Bounded context

Scheduled or event-triggered autonomous tasks built on the copilot's tool
executor but without an interactive user: e.g. "every Monday, draft
replenishment POs for items below reorder point and route for approval",
"when an anomaly is detected, open a triage ticket". Agents act under a
**service identity with explicitly granted permissions** and are always
bounded by budgets, allowlists, and human approval gates.

### 6.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `agents` | id, code, name, kind (`scheduled|event_triggered`), trigger JSON (cron or event name + filter), tool_allowlist JSON, actor_profile_id (service membership), budget JSON, status (`draft|active|paused|archived`) | tenant-scoped |
| `agent_actor_profiles` | id, name, membership_id (dedicated service user), role_ids JSON | least-privilege service identity; SoD applies |
| `agent_runs` | id, agent_id, started_at, finished_at, status (`running|succeeded|failed|budget_exceeded|blocked`), trigger_ref, summary JSON, cost | |
| `agent_run_actions` | id, run_id, tool, inputs_redacted JSON, outcome, ref (created doc id), audit correlation | append-only |
| `agent_guardrails` | id, agent_id, max_actions_per_run, max_cost_per_run, requires_human_approval (bool per action kind) | enforced by executor |

### 6.3 Execution model (normative)

1. **Identity**: an agent never runs as a human's session. It runs as its
   `agent_actor_profile`'s service membership, whose role assignments
   are granted by tenant admins (SoD-checked at grant time, [authorization & policy §7.2](../04-security/authorization-and-policy.md)).
   Token: internal client-credentials token issued per run, audience-bound,
   15-min TTL ([identity & token platform §3.3](../04-security/identity-and-token-platform.md)).
2. **Trigger**: scheduler (cron, per-tenant time zone) or event subscription
   (catalog events, filtered). Event-triggered agents dedupe on
   `event_id`.
3. **Plan → act loop**: the agent's prompt (tenant-authored from a
   template) + tool allowlist drive an LLM plan; each action passes the
   guardrail checks:
   - action kind in allowlist → else `blocked` + `agents.action.blocked`;
   - budget remaining → else `budget_exceeded`;
   - `requires_human_approval` actions are not executed: instead the
     agent creates a **draft + approval request** (workflow engine) and
     stops, notifying the owner.
4. **Immutability of guardrails**: agent owners cannot raise their own
   budgets/allowlists; changes require `agents:agent:configure`
   (sensitive) and are audited with before/after.
5. **Observability**: every run produces a run report (actions, refs,
   cost); runs are visible under `agents:run:view`; failures notify
   the agent owner.

### 6.4 Seeded agent templates (v1)

| Template | Trigger | Actions (all draft-and-route unless noted) |
|----------|---------|--------------------------------------------|
| Replenishment drafter | weekly cron | compute needs (supply-chain view) → draft POs → approval requests |
| Dunning assistant | daily cron | rank overdue invoices → send reminder notifications (template-bound, no free text) |
| Anomaly triager | `anomaly.case.detected` (severity ≥ high) | read case + audit links → draft triage note on the anomaly → notify queue |
| New-order sanity check | `checkout.order.placed` | read-only checks (address sanity, duplicate detection) → flag anomalies |
| Weekly KPI digest | weekly cron | KPI snapshots → notification digest to configured roles |

### 6.5 Event catalog

`agents.agent.created` / `.activated` / `.paused` /
`.archived`, `agents.run.started` / `.succeeded` /
`.failed` / `.budget_exceeded`, `agents.action.blocked`,
`agents.approval.requested` (human-in-the-loop handoff).

### 6.6 API surface (/api/v1/agents)

| Endpoint | Permission |
|----------|-----------|
| CRUD agents, actor profiles | `agents:agent:view|create|update`, `agents:agent:configure` (sensitive: budgets/allowlists/roles) |
| POST /agents/{id}:pause, :resume, :run_now | `agents:agent:update` / `agents:run:execute` |
| GET runs, run actions | `agents:run:view` |
| GET templates | `agents:agent:view` |

Scopes: `agents:read`, `agents:write`, fine sensitive
`agents:agent:configure`.

### 6.7 Integration points

Consumes: kernel events, workflow engine (approvals), notifications,
copilot tool executor (shared implementation), usage meters. Publishes to:
audit (every action), analytics (agent cost/effectiveness).

---
---

## Safety invariants (architecture-tested)

1. No Intelligence module writes to non-Intelligence tables (Deptrac +
   connection guard: Intelligence code paths bind the analytics connection
   for reads; writes only to `analytics_*`, `forecasting_*`,
   `anomal*`, `recommendation*`, `copilot_*`, `agent*`).
2. Copilot/agent tool executor re-checks permission per call under the
   acting identity; attempted out-of-allowlist calls are blocked + logged
   (`copilot.safety.blocked` / `agents.action.blocked`).
3. Sensitive-scope tools are invisible to client-credentials actors
   ([identity & token platform §5.2](../04-security/identity-and-token-platform.md)) — agents' service tokens follow the same rule.
4. Projections never expose cross-tenant rows; rebuild commands are
   platform-operator or tenant-admin (`analytics:export:create`-class)
   operations and are audited.

---
---

## AI principals & authorization (kernel rules)

AI actors are non-human principals governed by the kernel identity and authorization registers:

- **Acting identity**: copilot tool calls execute under the invoking user's identity and re-check permission per call; agents run as `agent` service principals with tenant-scoped memberships ([service identity](../04-security/identity-and-token-platform.md#sec-014-service-identity)).
- **Capability & policy rules**: agent profiles are role assignments subject to the capability model, explicit-DENY precedence, delegation boundaries, and SoD checks at configuration time ([authorization & policy](../04-security/authorization-and-policy.md)).
- **Decision log & safety**: every tool invocation is recorded in the authorization decision log; out-of-allowlist calls are blocked and logged (`copilot.safety.blocked` / `agents.action.blocked`); sensitive-scope tools are invisible to client-credentials actors ([SEC-024](../04-security/authorization-and-policy.md#sec-024-authorization-decision-log-and-ai-agent-safety)).
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| forecasting.* | 10¹–10² (runs + alerts) |
| anomaly.* | 10¹–10³ (depends on rule thresholds) |
| recommendations.* | 10² (outcome events) |
| copilot.* | 10³ (tool invocations dominate) |
| agents.* | 10²–10³ (runs + actions) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `anomaly_case` | anomaly | resolve (audit-graded) |
| `agent_run_approval` | agents | human approval of agent-proposed actions |
| `copilot_session` | copilot | none (budget-guarded, not workflow) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| forecasting | `SalesHistoryQuery` (analytics), `ReceivablesAging`/`PayablesAging` (ar/ap), `CashPositionQuery` (banking), subscriptions, suppliers contracts |
| anomaly | kernel events, analytics facts |
| recommendations | analytics facts, forecasting outputs, ar/inventory availability |
| copilot | every tool-mapped module API (authorized in-process) |
| agents | copilot tool executor, workflow engine, kernel events |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| forecasting | `forecasting:run:view|execute`, `forecasting:cash:view` |
| anomaly | `anomaly:case:view|triage`, `anomaly:rule:configure` (sensitive) |
| recommendations | `recommendations:model:configure` (sensitive); read/dismiss follow host-resource permissions |
| copilot | `copilot:use`, `copilot:session:view`, `copilot:settings:configure` (sensitive) |
| agents | `agents:agent:view|create|update`, `agents:agent:configure` (sensitive), `agents:run:view|execute` |
| (kernel) | `kernel:notifications:send` (sensitive — copilot/agent notification tool) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../04-security/authorization-and-policy.md) register and [ADR-0002](../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Forecast determinism**: same input snapshot → identical forecast lines;
   component payloads present for every line (explainability).
2. **Cash shortfall alert**: fixture AR/AP/recurring set projecting a
   negative week emits exactly one `forecasting.cash.shortfall_projected`.
3. **Anomaly block**: `anomaly.payment.velocity` triggered → next
   capture for that customer requires ACR-2 approval by a different
   membership; false_positive feedback clears future blocks.
4. **Copilot privilege test**: actor without `sales:order:create`
   asking the copilot to create an order → tool absent; with permission →
   draft created under the actor's identity; audit row links actor,
   conversation, and created order.
5. **Copilot budget**: exhausting the daily cap returns the graceful
   message and emits the event; no further tool calls execute.
6. **Agent human-in-the-loop**: an agent configured with
   `requires_human_approval` on PO creation produces drafts +
   approval requests and never posts an approved PO itself.
7. **Agent SoD**: granting an agent profile conflicting permissions is
   blocked at configuration time ([authorization & policy §7.2](../04-security/authorization-and-policy.md) applies to service
   memberships).
8. **Tenant isolation**: all tables + projections covered by
    `AssertTenantIsolation`; copilot conversations of tenant A are
    invisible to tenant B admins even with crafted conversation ids.
9. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../19-testing/testing-strategy.md).
