# Release Management

**Status:** Current
**Scope:** Tag-driven release process for the Navdhya monorepo.

---

## 1. Release types

| Type | Trigger | Artefacts | Deploy |
| --- | --- | --- | --- |
| **Patch** | `v*.*.x` tag | 4 standalone tarballs + checksums + attestation | Optional (gated) |
| **Minor** | `v*.x.0` tag | Same as patch | Optional (gated) |
| **Major** | `v*x.0.0` tag | Same as patch | Optional (gated) |
| **Hotfix** | `v*.*.*-hotfix.N` tag | Same as patch | Optional (gated) |

---

## 2. Release process

### 2.1 Pre-release

1. Ensure `main` is green on `ci-gate` and `security-gate`.
2. Review the changelog (conventional commits since last tag).
3. Decide the version bump (semver).
4. Confirm no open P1 incidents.

### 2.2 Tagging

```sh
git checkout main
git pull --ff-only
git tag -a v0.1.0 -m "Navdhya v0.1.0"
git push origin v0.1.0
```

A tag is immutable. If the release is wrong, create a new tag rather than moving it.

### 2.3 Release workflow

`.github/workflows/release.yml` runs automatically on a `v*` tag:

```
tag v* ──► verify (ci.yml) ──► artifacts ×4
                                  ├─ tarball + SHA-256
                                  ├─ SLSA build provenance
                                  └─ GitHub Release
                                        └─ deploy (staging, production)
                                             environment approval
                                             scripts/deploy/<env>.sh
```

Key conditions:

- `verify` reuses `ci.yml`; a tag cannot bypass the PR gate.
- `artifacts` builds with `NEXT_STANDALONE=1` and uploads `release-<app>`.
- `attest` creates build provenance for the tarballs.
- `publish` creates/updates the GitHub Release with notes and checksums.
- `deploy` only runs when `vars.DEPLOY_ENABLED == 'true'` and the event is a tag push (or manual dispatch with `dry_run=false`).
- Deploy environments are `staging` and `production`.

### 2.4 Manual dispatch

The workflow supports `workflow_dispatch` with `dry_run` (default `true`). A dry run builds and attests but does not deploy.

### 2.5 Post-release

1. Verify the GitHub Release contains all four tarballs and checksums.
2. Run smoke checks against the deployed environment.
3. Watch metrics for 15 minutes.
4. Update the deployment record.
5. Communicate the release to stakeholders.

---

## 3. Versioning

- **Major:** Breaking changes (API, data model, deployment).
- **Minor:** New features, backward-compatible.
- **Patch:** Bug fixes, documentation, non-breaking changes.
- **Hotfix:** Emergency fix on a released version; creates a new patch tag.

---

## 4. Release artefacts

| Artefact | Contents | Notes |
| --- | --- | --- |
| `navdhya-<app>-<tag>.tar.gz` | `.next/standalone/` | Currently omits `.next/static` and `public` (P0 gap) |
| `sha256sums-<app>.txt` | SHA-256 of the tarball | Verified before deploy |
| SLSA attestation | Build provenance | Links artefact to source commit |

---

## 5. Release notes

Generated from conventional commit history. The PR title convention ensures derivable release notes.

Format:

```
## v0.1.0 (2026-10-20)

### Features
- feat(customer): add saved-pandit empty state

### Bug fixes
- fix(api): reject replayed verification tokens

### Documentation
- docs(operations): add Vercel troubleshooting
```

---

## 6. Rollback

See [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md).

- Frontend: promote previous deployment (Vercel) or restore previous release directory (host).
- Backend: check out previous tag, reinstall, re-cache, restart. No down-migrations.
- Feature flag: turn the cutover flag off.

---

## 7. Related

- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) — deployment guide
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md) — recovery
- [`../operations/ENVIRONMENTS.md`](../operations/ENVIRONMENTS.md) — environment matrix
- [`change-management.md`](change-management.md) — change control
- [`../MNC-GRADE-PLAN.md`](../MNC-GRADE-PLAN.md) — master plan
