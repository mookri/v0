# Change Management

**Status:** Current
**Scope:** Controlling changes to the Navdhya platform.

---

## 1. Change types

| Type | Description | Approval | Lead time |
| --- | --- | --- | --- |
| **Standard** | Low-risk, routine changes (docs, tests, minor fixes) | PR review | None |
| **Normal** | Feature work, dependency updates, schema changes | PR review + CODEOWNERS | 1-2 days |
| **Emergency** | Security fix, production incident remediation | Security owner + platform lead | Immediate |
| **Major** | Breaking API change, framework upgrade, data migration | Platform lead + all affected owners | 1 week+ |

---

## 2. Change request process

### 2.1 Standard changes

1. Open a PR with a conventional title.
2. Complete the PR template.
3. Run local gates (`pnpm verify`, `pnpm check:architecture`).
4. Request review from CODEOWNERS.
5. Merge after approval and green checks.

### 2.2 Normal changes

1. Open an issue describing the problem and intended outcome.
2. Open a PR linked to the issue.
3. For cross-cutting changes, write an ADR.
4. For schema changes, include a compatibility plan (expand → contract).
5. For API changes, include a versioned contract if breaking.
6. Request review from all affected owners.
7. Merge after approval and green checks.

### 2.3 Emergency changes

1. Create a hotfix branch from the last release tag.
2. Implement the minimal fix.
3. Run targeted tests.
4. Tag a hotfix release (`v*.*.*-hotfix.N`).
5. Deploy to production with expedited approval.
6. File a postmortem within 5 business days.
7. Backport the fix to `main`.

### 2.4 Major changes

1. Write an RFC or ADR with full design.
2. Review with platform lead and affected owners.
3. Create a migration plan with rollback.
4. Implement in phases (expand → migrate → contract).
5. Test each phase independently.
6. Deploy with extended monitoring window.
7. Update documentation and runbooks.

---

## 3. Risk classification

| Risk | Examples | Required review |
| --- | --- | --- |
| **Low** | Docs, tests, formatting, minor UI | Any maintainer |
| **Medium** | Feature work, dependency updates, new endpoints | CODEOWNERS |
| **High** | Auth, payments, tenancy, session cookies, deploy workflows | Security + operations |
| **Critical** | Data migration, schema drop, secret rotation, infrastructure change | Platform lead + all affected |

---

## 4. Change freeze

During a change freeze (e.g., before a major release or during an incident):

- Only P1 security fixes and critical bug fixes are allowed.
- All other changes are queued.
- The freeze is announced in the team channel with start and end times.
- The release owner can lift the freeze.

---

## 5. Communication

| Change type | Communication |
| --- | --- |
| Standard | PR description |
| Normal | PR + issue link |
| Emergency | Team channel + incident record |
| Major | RFC + team review + stakeholder notification |

---

## 6. Related

- [`release-management.md`](release-management.md) — release process
- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) — deployment
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md) — recovery
- [`../../CONTRIBUTING.md`](../../CONTRIBUTING.md) — contribution rules
- [`../MNC-GRADE-PLAN.md`](../MNC-GRADE-PLAN.md) — master plan
