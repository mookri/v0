# ADR 001: Product Boundaries

## Status
Accepted

## Context
As the platform scales, the lack of defined product boundaries leads to tightly coupled code, making it difficult to maintain, test, and deploy independently. We need to clearly separate domains like 'customer', 'partner', 'admin', and 'public web'.

## Decision
We will enforce strict product boundaries corresponding to the route owners defined in route-ownership.yaml. Each product area (Web, Customer, Admin, Partner) will be treated as an isolated domain.
