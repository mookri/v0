# ADR-0068: Single Transport & Generated Contracts as Cross-App Surface

## Status
Accepted (2026-11-11)

## Context
Four Next.js apps (`web`, `customer`, `partner`, `dashboard`) independently duplicated HTTP clients, error envelopes, and status enumerations (`A-01`, `A-02`, `A-14`).

## Decision
Consolidate Laravel BFF transport into `@navdhya/api-client` (`createLaravelClient` / `BackendApiError` / `StepUpRequiredError`) and share canonical enums and schemas through `@navdhya/contracts`. Hand-written status tables or duplicate fetch wrappers inside `apps/**` are disallowed and caught by drift gates (`G-DRIFT-0`).

