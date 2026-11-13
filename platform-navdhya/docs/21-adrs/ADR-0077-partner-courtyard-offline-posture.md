# ADR-0077: Partner Courtyard Offline Posture

## Status
Accepted (2026-11-11)

## Context
Pandits and temple staff work in low-connectivity courtyards and need mobile ergonomics without caching PII, session tokens, or faking offline state transitions.

## Decision
Ship `apps/partner` Courtyard Mode with >= 48px touch targets, `<EntitySwitcher />` across Pandit/Temple/Vendor personas, `public/manifest.webmanifest`, and a strictly scoped `public/sw.js` that caches only immutable shell assets while excluding `/api/*`, `/earnings`, `/review`, `/onboarding`, and all non-GET mutations (`navigator.onLine === false` fails loudly with network guidance).

