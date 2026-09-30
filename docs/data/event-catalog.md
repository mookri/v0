# Event Catalog (seed)

Booking: BookingCreated/Confirmed/Cancelled/Rescheduled (owner booking, v1, internal). Payment: PaymentSucceeded/Failed (restricted, finance retention). Search: SearchPerformed/ResultClicked/ZeroResult (product). Communication: NotificationCreated/MessageDelivered/MessageFailed. Experimentation: ExperimentAssigned/Exposed. Identity: IdentityCreated/AuthenticationSucceeded/SessionCreated/SessionRevoked.
Full definitions (owner/version/PII/retention/consumers) live in `packages/events/src/registry.ts`.
