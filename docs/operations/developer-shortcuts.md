# Developer Shortcuts (§§14–15, paved road)

The right way must be the short way. Shortcuts mirror CI so local green
means CI green.

| Command | What | Mirrors |
|---------|------|---------|
| `pnpm verify` | typecheck + lint + test (turbo) | CI `validate` job |
| `pnpm check:architecture` | arch gates + manifest drift (9 declared) | CI `architecture` job |
| `pnpm domain:check <name>` | one package's manifest vs tree | — |
| `pnpm --filter <pkg> build` | single-package build | — |

Rules for new shortcuts:

- Every shortcut maps 1:1 to a CI step (no local-only gates, no CI-only gates).
- Generators (`domain:generate`, …) land only after the third manual
  repetition — see `tooling/scripts/` precedent (generators live with the
  one-shot migration scripts, documented on use).
- Affected runs (`turbo --filter=...[ref]`) stay documented-but-manual
  until the monorepo outgrows full runs; nightly stays full regardless
  (parallel-flake note in `migration-status.yaml` baselines).
