# Runbook — consent failure (token service outage / deny spike)

**Drill evidence:** `tests/signals/failure-drills.test.ts` (`consent-service-outage`); consent matrix `tests/signals/consent-matrix.test.ts`; B7 withdrawal SLO `tests/signals/privacy-deletion.test.ts`.

## Symptoms / detection

- Client `metrics().eligibleAttempted` climbing while `acknowledged` stays flat (fail-closed: attempted but never acknowledged).
- Collector 403 rate spiking (`no valid consent token`).
- Consent-token issuer (Postgres-backed consent record service) erroring or latency-spiking.
- Sudden DENIED/UNKNOWN shift in the consent ledger (could be an outage symptom OR a real user-action wave — distinguish before acting).

## Expected degradation mode

- **Fail-closed for new events**: without a valid per-batch consent token the collector 403s and batches stay queued on the client. Nothing flows on partial consent.
- **Essential business processing is unaffected**: consent governs analytics/advertising payloads only — checkout, booking, payments do not depend on the consent service.
- DENIED consent blocks emission at the SDK gate (`track()` returns false) — an explicit deny is respected immediately, not queued.

## Immediate actions

1. Confirm scope: token issuer down (all clients 403) vs. individual token verification failures (specific batches).
2. If the issuer is down: prioritize restoring it (it is a consent-record read path, not a write path — reads are cheap to fail over).
3. Do NOT lower the collector's verification strictness to "let traffic through" — that converts an outage into a consent violation.
4. If the deny spike is a real user wave: no action; the system is behaving correctly.

## Recovery

- Token issuer heals; client queues drain on flush (the queued batches carry their original envelopes; the token is fetched fresh per batch at send time).
- Delivery-SLO denominator is eligible-attempted only — the outage inflates attempts but the ratio recovers as acks resume.

## Verification

- Collector 403 rate back to baseline; client `deliveryRatio()` recovering.
- Consent ledger shows no GRANTED→flows-without-token gaps for the window.
- Withdrawal SLO (`WITHDRAWAL_BLOCK_SLO_MS = 15 min`) still met for any withdrawals during the window.
