# Runbook — Incident Response

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 13 §9

Severity ladder, shipped operator runbooks, and communication rules. Alert classes that open incidents are defined in [SRE operations](../20-sre/sre-operations.md); restore-from-backup targets live in [HA & DR](../20-sre/ha-dr.md).

---

## Incident response

### 9.1 Severity ladder

| Severity | Definition | Examples | Response |
|----------|------------|----------|----------|
| P1 | Confirmed breach, data leakage, platform down | tenant isolation failure in prod, audit chain broken, refresh-token reuse ring, unauthorized export | page immediately; 15-min ack; war room |
| P2 | Degraded service or credible threat | outbox lag > 15 min, webhook circuit breakers tripping cluster-wide, brute-force wave | business-hours ops; 4-h ack |
| P3 | Hygiene | quota warnings, certificate < 14 days, dependency advisory medium | dashboard; weekly |

### 9.2 Runbooks (shipped as operator docs with the platform)

1. **Suspected tenant data leakage**: freeze tenant (status `suspended`),
   snapshot audit + outbox, enumerate tokens/sessions (revoke lineage),
   preserve evidence, notify per tenant agreement.
2. **Token platform compromise (key leak)**: rotate JWT keys (7-day JWKS
   overlap, [identity & token platform §4.4](../04-security/identity-and-token-platform.md)), revoke all `jti` issued under old key for
   sensitive-scope tokens, force step-up platform-wide.
3. **Refresh reuse ring**: bulk lineage revocation, affected-user
   notification, IdP federation session review.
4. **Database failover**: promote replica, pin connections, verify audit
   chain continuity on new primary, resume outbox relay.
5. **Ransomware/region loss**: restore from encrypted backups per §8.2,
   tenant-by-tenant smoke suite before reopening.

### 9.3 Communication

- Tenant-facing status surface (operator-managed) + audit-log excerpt
  provided to affected tenants post-incident (contractual).
- Every P1 produces a postmortem with timeline from audit/request-id
  correlation ([kernel services §11.1](../07-kernel/kernel-services.md)) within 5 business days.

---