# ADR-0069: Booking Lifecycle Authority & TypeScript Codegen

## Status
Accepted (2026-11-11)

## Context
Frontend `apps/customer` and shared `@navdhya/contracts` carried a divergent booking status state machine that disagreed with `platform-navdhya/app/Modules/Booking/Domain/BookingLifecycle.php` (`A-03`).

## Decision
Declare `BookingLifecycle.php` the single authority for booking states and legal transitions. Generate TypeScript union types and transition tables into `@navdhya/contracts` and verify PHP-to-TS parity via automated contract tests.

