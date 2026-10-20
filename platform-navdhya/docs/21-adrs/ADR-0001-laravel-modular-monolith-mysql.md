# ADR-0001: Laravel modular monolith on MySQL 8 (not Nx/PostgreSQL microservices)

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

The reviewed master plan proposed an Nx monorepo on PostgreSQL with microservice extraction. Navdhya's existing, approved v1 architecture is a Laravel 12 modular monolith on MySQL 8 (PHP 8.2+, Redis, Passport, Blade admin). ERP workflows (order → invoice → posting, payroll runs, 3-way match) are deeply transactional and map naturally onto single-database ACID transactions within a modular monolith. The team and CI already standardize on the Laravel stack.

## Decision

Retain the Laravel modular monolith on MySQL 8 as the v1 platform shape, while adopting the master plan's *semantics* in full: Capability vs Authority vs Policy, the Working Context invariant, principal types, Delta isolation, and T0–T4 transaction classes. Colon capability syntax `<module>:<resource>:<action>` is retained (ADR-0002). Every module remains carve-out-safe so extraction stays a deployment choice, not an architectural commitment. Nx/PostgreSQL/microservice references in the source plan are treated as alternative-stack context, not directives.

## Consequences

Transactional ERP flows keep single-DB ACID semantics (T1/T2 classes) without distributed-transaction machinery. Extraction readiness is enforced by Deptrac + architecture tests instead of physical service boundaries. Deviation is recorded here and in the constitution's change-impact classes; no code/config change results from this ADR (documentation-only migration).
