# Communication

Transport + orchestration for facts emitted elsewhere. Owns: channel/provider routing, templates (versioned, schema-validated, sanitized), quiet-hours/frequency policy, dedup/idempotency keys, retry/backoff/DLQ, fallback chains, delivery state.
Never owns business truth; never mutated by business state. Provider SDKs live in `providers/` adapters only.
Retention: metadata long, bodies short (policy-controlled). PII redacted in logs.
