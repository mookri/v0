# Domain Manifests (§45)

Each capability that reaches L3+ carries a `domain.manifest.ts` at its
package root: machine-readable ownership, stage, and coupling. Validators
check claims against the tree; downstream docs are generated from these.

## Contract

Schema: `packages/contracts/src/governance/manifest.ts`
(`DomainManifest` + `validateManifestShape`).

| Field | Meaning |
|-------|---------|
| `domain` / `owner` / `sourceOfTruth` | must match package dir / ownership map |
| `database` | `'shared'` until the domain owns schema/database (§9) |
| `consumes` | other `@navdhya/*` packages imported under `src/` |
| `produces` | domain event names defined in `src/` |
| `deployable` / `stage` | extraction state (0 today everywhere) |
| `scalingProfile` / `dataClassification` | deployment + security planning input |

## Checking

```sh
pnpm domain:check booking payment   # named packages
node tooling/scripts/domain-check.js --all   # future gate: all packages
```

Drift policy:

- **Undeclared cross-package import → ERROR.** Fix the code or declare it
  in `consumes` (declaring is a design review, not paperwork).
- **Declared-but-unused `consumes` → WARNING.** Stale manifest; prune.
- **Missing manifest → ERROR** (only under `--all`; rollout below).
- **Missing `src/index.ts` → ERROR.** No public surface, no contract.

## Rollout

- Done: the 9 L5 packages (availability, booking, pandit, pricing, puja,
  recommendations, search, user, payment). All verified `consumes: []` —
  the decoupling is measured, not asserted.
- Next: L4/L3 packages as they promote (identity, content, notifications,
  fulfillment, …). L0 thin packages get manifests only if they survive
  the fold-or-define decision (`extraction-readiness.md`).
- `--all` becomes a CI gate when every surviving package has a manifest.

## Relation to other docs

- `extraction-readiness.md` scores; manifests declare. `domain:check`
  keeps them from drifting apart.
- `capability-ownership.yaml` stays the human-readable ownership map;
  manifests are the machine-readable mirror (domain/owner must match).
