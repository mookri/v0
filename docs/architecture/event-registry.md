# Event Registry (§21)

Mechanism: `packages/contracts/src/events/registry.ts`.

## Rules

1. Every business/integration event is registered with producer + version
   **before its second consumer exists**. First consumer + producer may
   move fast; the registry locks the contract at the moment sharing begins.
2. **Additive-only within a major version**: producers add optional fields,
   never rename or remove. Consumers declare `minVersion` and assert via
   `assertConsumerCompatible` at startup/handshake.
3. `schemaHash` pins the canonical schema alongside the event contract —
   a hash change without a version bump fails review, not runtime.
4. Envelope fields (eventId, eventType, eventVersion, occurredAt, producer,
   environment, actor, correlationId, causationId) ride the outbox envelope
   (`contracts/src/messaging/outbox.ts`) — never duplicated per event.

## Current state

- Registry + version/compat guards implemented and tested.
- Known producers today: booking
  (`BookingCreated/Confirmed/Cancelled/Rescheduled`), payment
  (`PaymentCaptured/Failed/Refunded`). No event has two consumers yet, so
  no registrations are live — registration lands with the second consumer
  (rule 1), not before.
- Consumer replay tests (golden event per version) land with the registry
  adoption, per consumer.

## Non-goals

Analytics events and metrics are not registered (different durability and
versioning needs). Commands/queries are not events.
