# ADR 006: Domain Routing

## Status
Accepted

## Context
Routing rules are implicit and scattered. Public, authenticated, and administrative routes live in the same space.

## Decision
We will adopt explicit Domain Routing. The route prefix will explicitly map to the product and capability owner, enforced via static analysis and middleware.
