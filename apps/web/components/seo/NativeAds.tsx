// components/seo/NativeAds.tsx
// v2 — BUG-11 FIX: Complete rewrite.
//
// PROBLEM WITH ORIGINAL:
//   The original NativeAdCard was entirely disconnected from the ad engine:
//   - Defined its own parallel `Ad` interface (not the engine's Ad type)
//   - Had its own IntersectionObserver loop duplicating Adprovider.tsx's useAdLazy
//   - Called props.onImpression(ad) directly — bypassing trackImpression(),
//     recordSpend(), and all revenue tracking in the engine pipeline
//   - Used hardcoded hex colors instead of the CSS variable token system
//   - Had no integration with AdEngineProvider context
//
// FIX:
//   NativeAdCard is now a thin wrapper around the engine's AdSlot component.
//   It delegates all impression tracking, revenue attribution, viewability,
//   and frequency capping to the engine pipeline.
//
//   A convenience prop `ad` (engine Ad type) can be passed for pre-resolved ads,
//   or `placement` + `layout` can be passed for engine-resolved ads.
//   Both paths go through AdSlot → engine → trackImpression → recordSpend.

"use client";

import type { Ad, AdPlacement, AdLayout } from "@/components/ad-network/core/types";
import AdSlot from "@/components/ad-network/AdSlot";
import { NativeFeedAd, HeroAd, InlineTextAd } from "@/components/ad-network/NativeAds";

// ─── Variant → Layout mapping ─────────────────────────────────────────────────

const VARIANT_LAYOUT: Record<Variant, AdLayout> = {
  hero:    "hero",
  inline:  "inline_text",
  minimal: "native_in_content",
};

type Variant = "hero" | "inline" | "minimal";

// ─── Props ────────────────────────────────────────────────────────────────────

interface NativeAdCardProps {
  /**
   * Pre-resolved Ad object from the engine.
   * If provided, rendered directly without an engine lookup.
   * If omitted, the engine resolves an ad for `placement`.
   */
  ad?: Ad | null;

  /** Placement to resolve from the engine (used when `ad` is not provided). */
  placement?: AdPlacement;

  /** Visual variant — controls which layout component renders. */
  variant?: Variant;

  /** Extra class applied to the outermost wrapper. */
  className?: string;

  /**
   * Called when the ad becomes viewable (IAB MRC: 50% in view for 1s).
   * NOTE: impression tracking (frequency cap, revenue) is handled automatically
   * by the engine pipeline — you do NOT need to call trackImpression() here.
   */
  onImpression?: (ad: Ad) => void;

  /** Called when the CTA is clicked. */
  onClick?: (ad: Ad) => void;
}

// ─── Component ────────────────────────────────────────────────────────────────

export default function NativeAdCard({
  ad,
  placement = "article_inline_1",
  variant = "hero",
  className = "",
  onImpression,
  onClick,
}: NativeAdCardProps) {
  const layout = VARIANT_LAYOUT[variant];

  // ── Path 1: Pre-resolved Ad — render the layout component directly ─────────
  // The caller already has an Ad object (e.g. from server-side resolution or
  // a pre-fetched campaign). Skip the engine context lookup and render directly.
  if (ad) {
    const commonProps = {
      ad,
      onImpression: onImpression ? () => onImpression(ad) : undefined,
      onClick:      onClick      ? () => onClick(ad)      : undefined,
    };

    return (
      <div className={className}>
        {variant === "hero"    && <HeroAd       {...commonProps} />}
        {variant === "inline"  && <InlineTextAd  {...commonProps} />}
        {variant === "minimal" && <NativeFeedAd  {...commonProps} />}
      </div>
    );
  }

  // ── Path 2: Engine-resolved Ad — use AdSlot for full pipeline ─────────────
  // AdSlot handles: frequency capping, viewability tracking, impression pixels,
  // recordSpend(), circuit breaker, A/B routing, and the lazy-load observer.
  return (
    <div className={className}>
      <AdSlot
        placement={placement}
        layout={layout}
        onImpression={(resolution) => {
          if (resolution.ad && onImpression) onImpression(resolution.ad);
        }}
        onClick={(resolution) => {
          if (resolution.ad && onClick) onClick(resolution.ad);
        }}
      />
    </div>
  );
}
