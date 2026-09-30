# Control Plane (§§11–12, 52, 54) — staged build

One package (`packages/config`), three layers. Build a layer only when its
second consumer arrives.

## L1 — typed config + flag evaluation (DONE)

- `config/src/flags.ts`: `FlagPolicy` + pure `evaluateFlag` (kill switch,
  environment gate, allow-lists, deterministic percentage rollout).
  Edge-safe (no imports, no I/O) by construction.
- `config/src/features.ts` raw booleans are **deprecated** — migrate one
  flag at a time to policies; the stubs stay false meanwhile.
- CONFIG vs SECRET vs FLAG taxonomy enforced by location: flags in
  policies, secrets in server env only (`SESSION_SIGNING_KEY` pattern),
  plain config in `environment`/`domains`.

## L2 — flag administration (not started)

Policies need storage + UI when the third flag lands. Shape (reserved):

```ts
{ key, policy: FlagPolicy, updatedBy, updatedAt, audit: true }
```

Admin mutates through audited endpoints (see `admin-operations.md`
requirements); evaluation stays the pure function from L1.

## L3 — versioned business policies (not started)

Pricing promotion versions and serviceability rules already carry
`policyVersion`; generalize to effective-dated policy records only when
the second policy consumer (after pricing) arrives.

## Explicitly out

Strangler env flags stay env-based: they gate the edge proxy where a
policy fetch would add a dependency to the hottest path. They migrate to
L2 only if per-request rollout (beyond on/off per class) is ever needed.
