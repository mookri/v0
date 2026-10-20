# ADR 005: Booking Ownership

## Status
Accepted

## Context
The booking flow is a complex transactional process currently intertwined with other domain logic.

## Decision
The `booking` capability will fully own the entire booking transaction lifecycle. No other domain can mutate booking states.
