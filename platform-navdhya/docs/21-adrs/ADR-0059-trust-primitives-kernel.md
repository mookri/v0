# ADR-0059: Trust Primitives Kernel (`App\Domain\Trust`) & Cross-Module Boundary

## Status
Accepted (2026-11-11)

## Context
Feedback, Support, and Incident workflows share deterministic case references, subject identifiers, party entitlements, state-machine assertions, audit hooks, and timeline shapes (`NV-*`), yet cross-module model imports previously risked tight coupling and boundary violations under Deptrac.

## Decision
Isolate shared Trust primitives (`TrustReferenceGenerator`, `FeedbackSubject`, `PartyEntitlement`, `TrustStateMachine`) in `app/Domain/Trust` and mirror TypeScript boundary types in `@navdhya/trust-contracts`. `App\Domain\Trust` MUST NEVER import `App\Modules\*`, and Trust modules communicate across module boundaries strictly via Contracts and Platform Outbox events.

