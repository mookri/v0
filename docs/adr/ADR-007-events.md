# ADR 007: Event-Driven Communication

## Status
Accepted

## Context
Synchronous cross-domain calls create tight coupling and reduce resilience.

## Decision
We will adopt an event-driven architecture for cross-domain communication. Domains will emit domain events (e.g. `BookingCreated`) rather than calling other domains' APIs directly.
