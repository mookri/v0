# ADR-0027 — Search realtime freshness

Status: Accepted (S63, 2026-10-18)
Extends: ADR-0024

## Context

v0 BFF corpus cache used a 10-minute TTL, so edits propagated slowly and
multi-instance BFFs diverged.

## Decision

- Platform `search_version` (ADR-0024) is the freshness authority.
- BFF `federateSearch` reads `search_version` from the `/search` response
  and invalidates the in-process corpus when it changes; corpus rebuilds
  on the next request.
- TTL reduced 10 min → 2 min as an idle-site backstop.
- Optional ping: `POST /api/search/revalidate` on the BFF, guarded by
  `SEARCH_REVALIDATE_SECRET`, calls `invalidateSearchIndex()` for
  sub-second propagation when platform observers are wired to it.
- Multi-instance convergence: each instance refreshes independently via
  version observation + TTL; no shared state required.

## Tests

- Web jest bm25/params still green; federated tests unchanged.