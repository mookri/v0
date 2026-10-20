# Signals release-readiness checklist (plan §13 family, B9)

Every item names the EVIDENCE that closes it. The meta-test
`tests/signals/release-readiness.test.ts` verifies each referenced artifact
exists and carries the pinned assertion — the checklist cannot rot
silently: deleting a cited test breaks the gate.

| # | Item | Evidence (test / artifact) |
|---|------|---------------------------|
| 1 | Instrumentation ownership: every event has a named owner + producer side | `packages/analytics/src/registry/events/index.ts` (owner/producer fields); `npm run registry:check` from `packages/analytics` |
| 2 | No double-fires: browser never emits server-owned (P0) events; server never emits client events | `packages/analytics/src/client/__tests__/client.test.ts` (server-produced rejection); `packages/analytics/src/server/emitters.ts` (producer gate) |
| 3 | Schema rejection fail-closed: unknown events/params rejected, surfaced, never silently dropped | `tests/signals/level1.test.ts`; drill `misdeployed-schema` in `tests/signals/failure-drills.test.ts` |
| 4 | Retry + dead-letter path: transient failure retries; attempts ceiling dead-letters with an alert seam | `services/functions/outbox-publisher/src/publisher.ts`; drill `pubsub-backlog` in `tests/signals/failure-drills.test.ts` |
| 5 | Duplicate-delivery idempotency: at-least-once collapses to exactly-once rows | `tests/signals/truth-path.test.ts`; load matrix truth leg `tests/signals/load-matrix.test.ts` (sinkRows == committed under redelivery) |
| 6 | Outbox-gap detection: every committed event persisted exactly once or in the gap report | `reconcileLevel1` assertions in `tests/signals/truth-path.test.ts` + `tests/signals/load-matrix.test.ts` |
| 7 | PII absence: contracts + redaction keep raw identifiers out of payloads | `tests/signals/level1.test.ts` (contract); `packages/analytics/src/crash/__tests__/hygiene.test.ts` (redaction); `packages/analytics/src/privacy/` (peppers, erasure) |
| 8 | Consent grant / deny / withdraw all exercised | `tests/signals/consent-matrix.test.ts` (grant/deny); `tests/signals/privacy-deletion.test.ts` (withdraw within SLO); drill `consent-service-outage` |
| 9 | Retention + deletion end-to-end: per-class pruning, fail-closed workflow, erasure suppression | `tests/signals/privacy-deletion.test.ts`; `tests/signals/privacy-rotation.test.ts`; `docs/analytics/retention-matrix.md` |
| 10 | 10k eps load test with the fault matrix (retries, duplicates, downstream failure, backlog, recovery) | `tests/signals/load-matrix.test.ts` (both legs) |
| 11 | Alerting operational: budget ladder, crash canary, nightly checks wired to owners | `packages/analytics/src/server/__tests__/budget.test.ts`; `tests/signals/crash-release.test.ts`; `tests/signals/warehouse-nightly.test.ts` |
| 12 | PostHog/CSP cleanup verified repository-wide | `docs/analytics/instrumentation-inventory.md` (B0 audit); CSP check in B0 record block `docs/analytics/signals-platform-plan.md` |
| 13 | Every §1 SLO demonstrated with recorded evidence, including recovery | `tests/signals/load-matrix.test.ts` (integrity + freshness); `tests/signals/failure-drills.test.ts` (drill results table: detection + recovery per drill); `tests/signals/rollback-drill.test.ts` (rollback within window) |
| 14 | Runbooks authored AND exercised at least once | `docs/analytics/runbooks/*.md` (7 files); each drill cites its runbook; `tests/signals/failure-drills.test.ts` asserts runbook existence per drill |
