# Contributing

Thank you for improving Navdhya. This repository is a production monorepo, so the bar is not "it works on my machine" — it is "the change is understood, tested, documented and safe to operate."

---

## 1. Read first

| Topic | Document |
| --- | --- |
| Local setup | [`docs/development/SETUP.md`](docs/development/SETUP.md) |
| Tests and CI | [`docs/development/TESTING.md`](docs/development/TESTING.md) |
| Architecture boundaries | [`docs/architecture/dependency-rules.md`](docs/architecture/dependency-rules.md) |
| Deployment | [`docs/operations/DEPLOYMENT.md`](docs/operations/DEPLOYMENT.md) |
| Security policy | [`SECURITY.md`](SECURITY.md) |
| Documentation index | [`docs/README.md`](docs/README.md) |

---

## 2. Workflow

1. **Pick or open an issue.** Describe the problem and the intended outcome before a large change.
2. **Branch from `main`.** Use a short, scoped name:
   - `feat/customer-referrals`
   - `fix/booking-timezone`
   - `docs/deploy-runbook`
   - `chore/dependency-refresh`
3. **Keep the change reversible.** Prefer small PRs; split refactors from behavior changes.
4. **Add or update tests** at the right layer. A bug fix needs a regression test.
5. **Update documentation** if behavior, configuration, commands, routes or operations change.
6. **Run the local gates** (see below).
7. **Open a PR** with a conventional title and a completed template.
8. **Respond to review.** Resolve conversations only after the change or rationale is recorded.

Do not commit directly to `main`, force-push a shared branch, or merge your own high-risk change without review.

---

## 3. PR titles and commits

The `pr-hygiene` workflow enforces a conventional PR title. Allowed types:

```
feat  fix  perf  refactor  docs  test  build  ci  chore  revert
```

Format:

```
<type>(optional-scope): <lower-case subject>
```

Examples:

```
feat(customer): add saved-pandit empty state
fix(api): reject replayed verification tokens
docs(operations): add Vercel troubleshooting
```

Commit messages follow the same spirit. Keep the subject imperative and lower-case; put the "why" in the body. Do not include secrets, customer data or generated logs.

---

## 4. Local gates

Run from the repository root:

```sh
pnpm verify
pnpm check:architecture
```

If you changed the backend:

```sh
cd platform-navdhya
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
./vendor/bin/deptrac analyse --no-cache
php artisan api:compose --check
```

If you changed a workflow, validate the YAML and the job logic. If you changed deployment, update [`docs/operations/DEPLOYMENT.md`](docs/operations/DEPLOYMENT.md) or the relevant runbook.

CI is the final gate. A local pass does not excuse a red `ci-gate` or `security-gate`.

---

## 5. Architecture and contract rules

- **Apps consume packages, not other apps.** Do not import `apps/web` from `apps/customer` or vice versa.
- **Packages do not import infrastructure drivers directly.** Use the declared port/adapter boundary.
- **BFF paths must resolve to real Laravel routes.** The contract-test allow-list stays empty. Do not add an exception to hide a missing route.
- **API responses keep the envelope.** `{ data, requestId }` or `{ error: { code, message, requestId } }`.
- **API changes are additive.** Breaking changes need a versioned contract and a migration plan.
- **Migrations are additive in staging/production.** Drops and renames are rejected; use expand → contract.
- **No cross-tenant access.** Tenant context must be explicit and fail closed.
- **No secrets in code.** Use `.env.example` for names only; see [`docs/operations/SECRETS.md`](docs/operations/SECRETS.md).

If a rule is genuinely wrong, amend the rule and its test with an inline rationale in the same PR. Do not weaken a gate silently.

---

## 6. Review expectations

- **CODEOWNERS** routes high-risk areas: auth/security/payments, backend modules, CI, docs.
- **High-risk changes** (auth, payments, tenancy, session cookies, deploy workflows) need an explicit security/operations review.
- **UI changes** include a screenshot or short recording in the PR.
- **Data changes** state the migration, backfill, rollback and compatibility plan.
- **Dependency changes** state why the upgrade is needed and whether the lockfile changed.
- **Workflow changes** state which required checks are affected and how a skipped job is handled.
- **Docs changes** keep links relative and do not duplicate canonical procedures.

Reviewers should block on correctness, security, data loss, broken contracts and missing tests. Style preferences should not block unless they affect maintainability or consistency.

---

## 7. Definition of done

- [ ] The change is scoped and reversible.
- [ ] Tests cover the behavior and the failure mode.
- [ ] `pnpm verify` and `pnpm check:architecture` pass (or the relevant subset is documented).
- [ ] Backend checks pass if backend code changed.
- [ ] No secret, token, customer record or production identifier is added.
- [ ] Docs/runbooks are updated for behavior, config or operational changes.
- [ ] The PR title is conventional and the template is complete.
- [ ] Required checks are green, or the exception is explicit and approved.
- [ ] The rollback path is known for a deployable change.

---

## 8. Security

Do not report vulnerabilities in a public issue. Follow [`SECURITY.md`](SECURITY.md). If you accidentally commit a secret:

1. Assume it is compromised.
2. Revoke/rotate it immediately.
3. Tell the maintainers privately.
4. Do not rewrite history without coordination.

See [`docs/operations/SECRETS.md`](docs/operations/SECRETS.md).

---

## 9. Documentation

- A behavior change updates the page that describes it in the same PR.
- Operational pages carry a **Status** line: **Current**, **Target** or **Historical**.
- Link to the canonical procedure instead of copying it.
- Use placeholders, never live values.
- Windows and Linux commands should both be clear; label them when they differ.

---

## 10. Conduct

Be direct about the work and respectful to the people. Harassment, discrimination, doxxing and deliberate disruption are not acceptable. Maintainers may remove comments or contributions that violate these expectations, and may block repeat offenders.

---

## 11. Deployment and releases

Do not tag or deploy from a feature branch. Release steps, approvals and rollback are in:

- [`docs/operations/DEPLOYMENT.md`](docs/operations/DEPLOYMENT.md)
- [`docs/operations/ROLLBACK.md`](docs/operations/ROLLBACK.md)
- [`docs/operations/SECRETS.md`](docs/operations/SECRETS.md)

If you are unsure whether a change is deployable, ask before merging.
