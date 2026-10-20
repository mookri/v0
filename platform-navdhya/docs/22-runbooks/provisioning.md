# Runbook — Tenant Provisioning

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** control plane CTRL-010…012, CTRL-016, CTRL-018/019

Operational view of the control-plane provisioning pipeline. The normative register entries live in the [control plane](../08-control-plane/control-plane.md); this runbook is the procedure operators follow and the control plane automates.

---

## Pipeline steps

1. **Create tenant record** — central registry row with lifecycle state `provisioning` (tenant lifecycle states: [tenancy & data](../06-tenancy/tenancy-and-data.md)).
2. **Resolve plan → blueprint** — the tenant's plan resolves to a blueprint (desired state): entitled modules, configuration classes, feature flags ([CTRL-002](../08-control-plane/control-plane.md#ctrl-002-plans-and-entitlements), [CTRL-005](../08-control-plane/control-plane.md#ctrl-005-tenant-blueprint--desired-state)).
3. **Validate blueprint** — schema + entitlement validation before any side effect ([CTRL-006](../08-control-plane/control-plane.md#ctrl-006-blueprint-validation)).
4. **Install entitled modules** — per-module schema application + seed data through the module manifest ([CTRL-003](../08-control-plane/control-plane.md#ctrl-003-module-registry-and-manifest)).
5. **Apply configuration** — configuration precedence platform → plan → tenant → org → module → user, each class under its declared precedence ([CTRL-013](../08-control-plane/control-plane.md#ctrl-013-configuration-precedence)).
6. **Activate flags** — feature flags per the feature state model installed/licensed/configured/activated/healthy ([CTRL-004](../08-control-plane/control-plane.md#ctrl-004-feature-state-model), [CTRL-015](../08-control-plane/control-plane.md#ctrl-015-module-activation-gating)).
7. **Verify health** — module health checks roll up into tenant health; the tenant is not licensed for production traffic until health passes ([CTRL-016](../08-control-plane/control-plane.md#ctrl-016-health-contract)).
8. **Smoke & handoff** — automated smoke suite, lifecycle state → `active`, `platform.tenant.provisioned` emitted ([CTRL-019](../08-control-plane/control-plane.md#ctrl-019-control-plane-events)).

## Resumability, idempotency & failure recovery

- **Resumable**: the pipeline records stage progress; a failed provisioning run continues from its last checkpoint rather than restarting ([CTRL-010](../08-control-plane/control-plane.md#ctrl-010-provisioning-pipeline)).
- **Idempotent**: re-running a succeeded stage is a no-op; idempotency keys + stage checkpoints prevent duplicate schema application, double seeding, or duplicate flag activation ([CTRL-011](../08-control-plane/control-plane.md#ctrl-011-provisioning-idempotency)).
- **Failure**: a failed run leaves the tenant in a named partial state, never an undefined one; recovery resumes or compensates per failure class; partial tenants never serve production traffic ([CTRL-012](../08-control-plane/control-plane.md#ctrl-012-provisioning-failure-and-recovery)).
---

## Verification requirements

Standing control-plane suites ([CTRL-018](../08-control-plane/control-plane.md#ctrl-018-reconciliation-verification)), each mapped to a named test in the [testing strategy](../19-testing/testing-strategy.md):

1. Provisioning is idempotent (double-run is a no-op).
2. Reconciliation is convergent (worklist drains).
3. Drift is detected and alerted.
4. Configuration precedence resolves deterministically.
5. Unlicensed module access is denied with the stable envelope code.