# Search Operations

Rebuild: canonical DB → Document Builder → new index → validate (counts, schema, visibility) → alias switch → retire old.
Lag: `indexedAt - occurredAt` tracked p50/p95/p99 per entity type. DLQ: failed index events with `{eventId,entityId,version,failureCode,attempts}` replayable (idempotent).
Rollback: ranking policy version downgrade via config; index rollback via alias switch.
Leak response: freeze indexing, audit public docs for customer/payment markers, rotate provider secrets.
