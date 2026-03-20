# Ads Setup and Production Notes

## Source of Truth

The live ad runtime in this repo is the hybrid orchestration stack under `lib/ad-orchestration/`.

Do not treat the legacy `components/ad-network/core/*` engine as the primary production path for article rendering. The production path is:

1. `PlacementEngine` creates placement opportunities
2. `AdDecisionEngine` evaluates candidates
3. `AuctionEngine` clears the slot
4. `LayoutResolver` chooses the best layout
5. `AdSlot` renders a reserved shell and chosen creative
6. `TrackingService` batches analytics events

## Inventory

Canonical campaigns are seeded in:

```txt
lib/ad-orchestration/campaign-seed.ts
```

Local runtime repository:

```txt
lib/ad-orchestration/CampaignRepository.ts
```

When adding inventory, always define:

- Placement compatibility
- A wide layout list, not one hardcoded layout
- Budget model and floors
- Frequency profile
- Scheduling
- Creative metadata
- Experiment metadata where applicable

## Layout Catalog

The supported layout catalog is defined in:

```txt
lib/ad-orchestration/layout-catalog.ts
```

This file is the production contract for:

- Supported layouts
- Supported placements
- Preferred placement mappings
- Reserved size contracts
- Bandwidth safety
- Fallback chains
- Visual family and template selection

If a layout is not in that catalog, it is not part of the automatic orchestration path.

## Automatic Rendering

Automatic rendering happens through:

```txt
components/ad-network/AutoAdRenderer.tsx
```

The renderer is recipe-driven. It does not rely on per-layout manual wiring for most cases. Layouts are grouped into templates such as:

- `hero`
- `poster`
- `split`
- `ribbon`
- `story`
- `video`
- `carousel`
- `interactive`
- `ticker`

That allows the system to support 100+ layouts without building 100 disconnected ad components.

## API Routes

Server routes:

- `app/api/ads/decision/route.ts`
- `app/api/ads/track/route.ts`
- `app/api/ads/track-beacon/route.ts`

These routes are store-agnostic now and can be moved behind Redis/Postgres-backed adapters later without changing the frontend contract.

## SEO Article Integration

Article pages use:

```txt
components/seo/article-ad-experience.ts
```

That helper:

- Builds the content plan on the server
- Precomputes above-the-fold ad decisions
- Generates a stable base request for the client runtime

`ArticlePage.tsx` passes those precomputed decisions into `ArticlePageClient.tsx`, which boots `AdRuntimeProvider` with `initialDecisions`.

## Production Hardening Checklist

- Keep ad dimensions deterministic to avoid CLS
- Avoid direct business logic in React components
- Route all impression and click attribution through `DecisionResponse.tracking`
- Use `prefetchedSlotIds` to avoid duplicate decision fetches
- Keep experiments deterministic with stable identities
- Keep low-bandwidth fallbacks in every important placement family

## Recommended Future Infra Bindings

The current in-repo runtime is intentionally local-first. Production-grade backing services should replace the in-memory implementations in `runtime.ts`:

- Redis for hot frequency and idempotency keys
- Postgres for campaign, budget, and experiment metadata
- Queue or event bus for analytics export
- Warehouse sink for ranking and auction feedback loops

## Test Surface

Current test coverage should include:

- Ranking behavior
- Auction clearing behavior
- Placement planning
- Route-level tracking and dedupe
- AdSlot render behavior
- Layout catalog contract coverage
- SEO article ad experience coverage

If you change placement mappings, resolver scoring, or renderer templates, add tests in the same change.
