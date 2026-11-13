# ADR-0075: Turborepo 2.11.5 Task Graph & Workspace Boundaries

## Status
Accepted (2026-11-11)

## Context
Turborepo 2.11.5 configuration differs across releases (`AGENTS.md` bundled-docs mandate), and PHP (`platform-navdhya/`) + Rust/aux services (`services/**`) coexist with the PNPM workspace.

## Decision
Retain `services/**` in `pnpm-workspace.yaml` and keep Laravel inside `platform-navdhya/`. Follow the installed `turbo@2.11.5` bundled docs for `globalEnv`, task `inputs`, `env`, and caching; keep experimental Boundaries non-blocking while enforcing TypeScript/architecture import gates via Vitest/PHPUnit.

