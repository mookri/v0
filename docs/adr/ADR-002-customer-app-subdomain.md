# ADR 002: Customer App Subdomain

## Status
Accepted

## Context
The customer portal containing authenticated routes (e.g. `/user`, `/settings`, `/book`) currently shares infrastructure and routing logic with public-facing pages, potentially causing security risks and bloat.

## Decision
We will extract the customer application into its own logical subdomain, decoupling its dependencies from the public marketing and SEO routes.
