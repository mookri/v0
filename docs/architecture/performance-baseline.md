# Performance Baseline

> Refreshed 2026-10-01 (was: build ~85s / typecheck 43s / test 17s — stale).

- Typecheck (`turbo run typecheck`): 40 tasks, all successful.
- Unit suites (`vitest run`): 58 files / 231 tests passing, wall ~6s.
- Architecture gates: 30 files / 84 tests passing, wall ~3s.
- Full `turbo run build` (Next standalone, 3 apps): NOT re-measured this
  pass — re-measure before staging deploy (phase 19 gate).

Budget guidance (staging gate): typecheck must stay fully green; unit suite
wall >60s triggers suite-splitting; app build failures block cutover.
