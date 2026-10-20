## Summary

<!-- What changed, and why. Link the issue if one exists. -->

## Type of change

- [ ] `feat` — new behavior
- [ ] `fix` — bug fix
- [ ] `perf` — performance
- [ ] `refactor` — no behavior change
- [ ] `docs` — documentation only
- [ ] `test` — tests only
- [ ] `build` / `ci` — tooling or pipeline
- [ ] `chore` — maintenance
- [ ] `revert` — revert

## Testing

<!-- Commands run and the result. Include the failing test for a bug fix. -->

```sh
pnpm verify
pnpm check:architecture
```

- [ ] Backend checks run if `platform-navdhya/` changed
- [ ] Manual smoke test described below (if applicable)

## Deployment and rollback

<!-- Delete if not deployable. -->

- Affected surface(s):
- Environment(s):
- Migration? (additive / none):
- Rollback:
- Feature flag:

## Checklist

- [ ] No secrets, tokens, customer data or production identifiers added
- [ ] Tests cover the behavior and failure mode
- [ ] Docs/runbooks updated for behavior, config, routes or operations
- [ ] Contract allow-list remains empty (no hidden BFF route exceptions)
- [ ] API changes are additive, or a versioned plan is linked
- [ ] `NEXT_PUBLIC_*` contains no secret
- [ ] Workflow changes note affected required checks
- [ ] UI changes include a screenshot/recording

## Screenshots / evidence

<!-- Before/after, traces, logs (redacted), or deployment evidence. -->

## Related

<!-- Issue, ADR, runbook or follow-up. -->
