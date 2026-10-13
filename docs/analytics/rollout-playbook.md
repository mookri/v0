# Signals rollout playbook (per surface)

Governing plan: `docs/analytics/signals-platform-plan.md` §3 + B9. This is
the operational form of the rollout strategy: a state machine every surface
passes through, with the gates that move it forward and the evidence that
closes it. Surfaces: web (apps/web), customer (apps/customer), vendor,
partner — plus any future surface emitting through the client SDK.

## The state machine

Every surface starts at `REGISTERED` and advances one gate at a time. A
surface never skips a gate; a regression returns it to the previous state.

| # | State | Entry gate (evidence required to leave the previous state) |
|---|-------|-----------------------------------------------------------|
| 1 | `REGISTERED` | Event inventory reviewed; every event the surface emits is in the registry (`registry:check` green); schemas + server validation deployed. |
| 2 | `SHADOW` | SDK deployed with `source: shadow` (the B3 default) and the surface's kill switch ON. Events flow to the collector with shadow provenance — they land in `signals_raw` with `source = shadow` and are excluded from first-party marts. |
| 3 | `COMPARING` | Shadow volume exists. Comparison report (`shadow-report`) covers: event counts vs. expected inventory, ID integrity (no gaps in session/event sequences), schema-error rate (quarantine rate ≈ 0), and collector latency within budget. |
| 4 | `SINKS_ACTIVE` | Comparison accepted. Sinks activated one by one — GA4 tag ownership (B4), first-party marts (B5) — each verified independently before the next. |
| 5 | `CONSENT_VERIFIED` | Consent grant/deny/withdraw exercised on the surface; key-conversion events (`payment_succeeded`, `booking_confirmed`) verified end-to-end through the P0 path (outbox → warehouse) with Level-1 reconciliation clean. |
| 6 | `TRAFFIC_RAISED` | Kill switch raised gradually (10% → 50% → 100%). Each step carries reconciliation + cost checks: `reconciliation_ok` green, budget classify below the 50% rung's alert, no new quarantined classes. |
| 7 | `OBSOLETE_REMOVED` | Two consecutive clean weekly cycles at full traffic. Obsolete instrumentation (old GA4 tags, ad-hoc gtag calls, dead GTM tags) removed and the inventory re-reviewed; CSP re-verified (no PostHog or removed-vendor allowances lingering). |

## Shadow-window durations

- **Low-risk behavioral signals** (page views, search, engagement): a
  **two-week** shadow window before `COMPARING` is accepted — enough to see
  weekday/weekend shapes and catch session-id anomalies.
- **Revenue and booking events** (`payment_succeeded`, `payment_failed`,
  `booking_confirmed` — the P0 family): the calendar does NOT close these.
  Activation requires **explicit reconciliation criteria** regardless of
  window length: Level-1 clean for the full shadow period, key-event parity
  vs. the legacy source within the agreed tolerance, and a signed-off
  comparison report. Time alone never promotes a revenue event.

## Cost checks at every traffic step

- `classifyBudgetUsage` (B9 budget module) run against the current month:
  any `warn_80` finding pauses further traffic increase until explained.
- Warehouse-lint green on the surface's new/changed models before sinks
  activate (partition filters + budget annotations are entry criteria, not
  afterthoughts).

## Removing obsolete instrumentation

The last gate is deletion, and it is gated on evidence, not calendar:

1. Inventory diff: what the surface emitted before Signals vs. after.
2. For each obsolete tag/call-site: confirm the Signals equivalent is live
   AND reconciled (not merely shadowed).
3. Remove; re-run the surface's comparison report one cycle; CSP header
   updated and re-verified.
4. Record the removal in `instrumentation-inventory.md` + CHANGELOG.

## Failure paths during rollout

Each state can regress; the runbooks in this directory own the response:
`collector-outage`, `pubsub-backlog`, `warehouse-freshness`,
`schema-rollback`, `consent-failure`, `analytics-drift`, `crash-spike`.
A surface in `TRAFFIC_RAISED` that trips a §1 SLO returns to its last
clean traffic step — the kill switch (`NEXT_PUBLIC_SIGNALS_ENABLED`)
returns it to `SHADOW`-safe no-op semantics without touching business
flows.
