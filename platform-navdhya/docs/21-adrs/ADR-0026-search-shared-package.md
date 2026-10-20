# ADR-0026 — Shared search package and app adoption

Status: Accepted (S62, 2026-10-18)
Extends: ADR-0024, ADR-0025

## Context

The BM25 engine, tokenizer and params lived only in `apps/web/lib/search`,
so customer/partner could not reuse them. The platform already shipped
`packages/search` (domain-driven) but without the BM25 engine.

## Decision

- Add `tokenizer.ts` (v2, Devanagari fold + bigrams), `transliteration.ts`,
  `bm25.ts` (Bm25Index + IndexableDoc + ScoredDoc) and `params.ts` to
  `packages/search`, re-exported from `src/index.ts`.
- `apps/web/lib/search/bm25.ts` and `params.ts` become re-export shims so
  existing imports keep working; web tests still pass (8 + 6).
- Customer and partner apps get thin BFF proxies at `/api/search` and
  `/api/search/suggest` forwarding to the platform public plane.

## Consequences

- One search engine implementation across apps; tokenizer parity with the
  PHP `SearchNormalizer` (same fold map).
- Customer/partner search is platform-authoritative (no in-process index
  yet — that lands with S63 freshness work if needed).