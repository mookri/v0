# ADR-0078: Web Content Source of Truth & Calendar Keying

## Status
Accepted (2026-11-11)

## Context
`apps/web` SEO, Panchang/Muhurat calendar pages, and analytics beacons require a single backend source of truth, cache invalidation keyed safely against DoS/traversal, and strict PII exclusion (`A-17`).

## Decision
Serve canonical ritual/calendar content through Laravel API (`SEO_CONTENT_SOURCE=api` / tagged ISR cache entries invalidated via rate-limited, HMAC-verified `POST /api/revalidate`), format all Panchang/Muhurat intervals unambiguously in IST via `@navdhya/date-time`, and enforce PII-free analytics event schemas (`FORBIDDEN_ANALYTICS_PII_KEYS` in `tracking-schema.ts`).

