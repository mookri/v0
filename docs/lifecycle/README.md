# Application Lifecycle

**Status:** Current
**Scope:** Index of lifecycle documentation for the Navdhya platform.

This page is an index. The detailed procedures live in the linked documents.

---

## Lifecycle documents

| Document | Description |
| --- | --- |
| [`application-lifecycle.md`](application-lifecycle.md) | Provision through retirement, environment and tenant lifecycles |
| [`release-management.md`](release-management.md) | Tag-driven release process |
| [`change-management.md`](change-management.md) | Change control and risk classification |

---

## Lifecycle stages (summary)

```
Provision → Configure → Deploy → Monitor → Scale → Update → Retire
```

| Stage | Summary | Detail |
| --- | --- | --- |
| Provision | Create environment, DNS, TLS, database, secrets | `application-lifecycle.md` §2.1 |
| Configure | Set env vars, flags, tenant context | `application-lifecycle.md` §2.2 |
| Deploy | Build, package, verify, unpack, switch, restart, smoke | `application-lifecycle.md` §2.3 |
| Monitor | Health checks, logs, alerts, SLOs | `application-lifecycle.md` §2.4 |
| Scale | Add capacity when triggers fire | `application-lifecycle.md` §2.5 |
| Update | Patches, upgrades, framework changes | `application-lifecycle.md` §2.6 |
| Retire | Drain, archive, revoke, remove, postmortem | `application-lifecycle.md` §2.7 |

---

## Environment lifecycle

See `application-lifecycle.md` §3 for the environment stage matrix.

## Tenant lifecycle

See `application-lifecycle.md` §4 for the tenant state machine (aligned with TEN-015).

---

## Related

- [`../deployment/README.md`](../deployment/README.md) — per-app deployment
- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) — canonical deploy guide
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md) — recovery
- [`../MNC-GRADE-PLAN.md`](../MNC-GRADE-PLAN.md) — master plan
