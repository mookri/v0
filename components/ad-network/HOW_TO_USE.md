# Hybrid Ad Orchestration Engine

This repo now uses a hybrid ad system:

- Server-side decisioning through `/api/ads/decision`
- Server-side tracking through `/api/ads/track` and `/api/ads/track-beacon`
- Thin client `AdSlot` rendering with reserved dimensions and shared observers
- Automatic layout selection across 100+ supported layouts

## Architecture

Core engine modules live in `lib/ad-orchestration/`:

- `AdDecisionEngine.ts`
- `RankingEngine.ts`
- `AuctionEngine.ts`
- `PlacementEngine.ts`
- `LayoutResolver.ts`
- `LayoutRegistry.ts`
- `layout-catalog.ts`
- `ExperimentEngine.ts`
- `FrequencyPacingService.ts`
- `runtime.ts`

Client runtime modules live in `components/ad-network/runtime/`:

- `AdRuntimeProvider.tsx`
- `TrackingService.ts`
- `observer.ts`

## Quick Start

Wrap the app once at the root:

```tsx
"use client";

import type { ReactNode } from "react";
import { AdProvider as AdThemeProvider } from "@/components/ad-network/Adprovider";
import { AdRuntimeProvider } from "@/components/ad-network/runtime/AdRuntimeProvider";

export function AdProvider({ children }: { children: ReactNode }) {
  return (
    <AdRuntimeProvider>
      <AdThemeProvider>{children}</AdThemeProvider>
    </AdRuntimeProvider>
  );
}
```

Render a slot with a dynamic opportunity:

```tsx
import AdSlot from "@/components/ad-network/AdSlot";

<AdSlot opportunity={slotOpportunity} />
```

Compatibility props still work for non-migrated callers:

```tsx
<AdSlot placement="article_inline_1" />
```

The engine will create a compatibility opportunity automatically, but new code should prefer `opportunity`.

## Automatic Layout System

Supported layouts are defined in `lib/ad-orchestration/layout-catalog.ts`.

- The catalog covers 100+ layouts across native, horizontal, vertical, sidebar, sponsored, carousel, video, email, app, CTV, and fallback families.
- `LayoutResolver` scores candidate layouts using placement, surface, depth, bandwidth, viewport, and creative capability.
- `AutoAdRenderer.tsx` renders layout families automatically from the selected layout metadata.

Current renderer templates include:

- `hero`
- `poster`
- `split`
- `ribbon`
- `story`
- `video`
- `carousel`
- `interactive`
- `ticker`

This means layouts like `native_in_content`, `medium_rectangle`, `inline_text`, `half_page`, `card_horizontal`, `sponsored_section`, `carousel_product`, and `story_swipeable` all render through one consistent automatic system.

## Article / SEO Integration

SEO article pages now build ad plans and above-the-fold decisions on the server.

Use:

```ts
import { buildArticleAdExperience } from "@/components/seo/article-ad-experience";
```

That helper returns:

- `contentPlan`
- `initialDecisions`
- `baseRequest`
- `prefetchedSlotIds`

`components/seo/ArticlePage.tsx` uses that helper, and `ArticlePageClient.tsx` passes `initialDecisions` into `AdRuntimeProvider` so the first slots render without waiting for a client fetch.

## Adding Inventory

Canonical inventory seed lives in:

```txt
lib/ad-orchestration/campaign-seed.ts
```

Add or update campaigns there by setting:

- `placements`
- `layouts`
- `budget`
- `frequencyProfile`
- `targeting`
- `creative`
- `experiment`

For automatic rendering, prefer broad `layouts` arrays so the resolver can pick the best fit for each placement.

## Decision Flow

The runtime path is:

1. Build opportunities with `PlacementEngine`
2. Fetch candidates from `CampaignRepository`
3. Filter by schedule, targeting, and pacing
4. Assign experiments
5. Rank by predicted eCPM and quality
6. Run auction and floor logic
7. Resolve best layout
8. Return `DecisionResponse`
9. Render `AdSlot`
10. Track `render`, `impression`, `viewable`, and `click`

## Tracking

Tracked events include:

- `render`
- `impression`
- `viewable`
- `click`

The client batches events and uses `sendBeacon` for unload-safe delivery. The server routes dedupe events with idempotency keys and update experiment feedback plus spend/frequency side effects.

## Layout Safety Rules

- Every layout reserves a deterministic size through `LayoutRegistry`
- `AdSlot` renders skeleton space before loading content
- Shared observers prevent per-slot observer leaks
- Viewability waits for 50% visibility for 1 second for display units

## Extending Layouts

To add a new layout:

1. Add the layout name to `AdLayout` in `components/ad-network/core/types.ts`
2. Add it to the correct family in `lib/ad-orchestration/layout-catalog.ts`
3. Adjust placement defaults if needed
4. If the layout needs a special template, update `AutoAdRenderer.tsx`
5. Add a contract test

## Testing

Relevant test files:

- `lib/ad-orchestration/__tests__/RankingEngine.test.ts`
- `lib/ad-orchestration/__tests__/AuctionEngine.test.ts`
- `lib/ad-orchestration/__tests__/PlacementEngine.test.ts`
- `app/api/ads/__tests__/routes.test.ts`
- `components/ad-network/__tests__/AdSlot.test.tsx`

Recommended additions for any new work:

- Layout contract coverage
- Resolver selection coverage
- Article ad experience coverage
- Renderer coverage for any new template type
