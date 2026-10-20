# ADR 003: Monorepo Architecture

## Status
Accepted

## Context
We are extracting services and breaking down the monolith into distinct products (Web, Customer, Admin, Partner), but we want to retain code-sharing for capabilities like design system, core logic, and UI components.

## Decision
We will adopt a monorepo architecture (e.g. Turborepo) to host all applications and shared packages, ensuring unified tooling while preserving modularity.
