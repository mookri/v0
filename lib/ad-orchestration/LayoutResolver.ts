import type { Ad, AdLayout, ClientHints } from "@/components/ad-network/core/types";
import { layoutRegistry } from "./LayoutRegistry";
import type { LayoutDiversityContext, LayoutSelection, PlacementOpportunity } from "./types";

function getBandwidthClass(client: ClientHints): "low" | "normal" | "high" {
  if (client.saveData) return "low";
  if (client.connection === "2g" || client.connection === "3g") return "low";
  if (client.connection === "5g" || client.connection === "wifi" || client.connection === "ethernet") return "high";
  return "normal";
}

function hasImage(ad: Ad): boolean {
  return Boolean(
    ad.creative?.imageUrl ||
    ad.creative?.imageUrlWebp ||
    ad.imageUrl ||
    ad.creative?.videoPosterUrl ||
    ad.creative?.thumbnails?.length,
  );
}

function hasVideo(ad: Ad): boolean {
  return Boolean(ad.creative?.videoUrl || ad.creative?.videoUrlHls);
}

function intentScore(opportunity: PlacementOpportunity, layout: AdLayout): number {
  const spec = layoutRegistry.getSpec(layout);
  let score = 0;

  if (opportunity.preferredFamilies?.includes(spec.family)) score += 22;
  else if (opportunity.preferredFamilies?.length) score -= 6;

  if (opportunity.preferredTemplates?.includes(spec.template)) score += 26;
  else if (opportunity.preferredTemplates?.length) score -= 8;

  if (opportunity.designIntent === "editorial") {
    if (spec.family === "native" || spec.family === "sponsored") score += 12;
    if (spec.template === "split" || spec.template === "poster") score += 12;
  }

  if (opportunity.designIntent === "compact") {
    if (spec.density === "compact") score += 16;
    if (spec.template === "ribbon" || spec.template === "ticker") score += 14;
    if (spec.template === "story" || spec.template === "hero" || spec.template === "video") score -= 20;
  }

  if (opportunity.designIntent === "immersive") {
    if (spec.density === "immersive") score += 16;
    if (spec.template === "story" || spec.template === "carousel" || spec.template === "interactive" || spec.template === "poster") score += 14;
    if (spec.template === "ticker") score -= 22;
  }

  if (opportunity.designIntent === "trust") {
    if (spec.family === "sidebar" || spec.family === "display" || spec.family === "sponsored") score += 12;
    if (spec.template === "poster" || spec.template === "split") score += 10;
  }

  if (opportunity.reservedSize.height <= 140) {
    if (spec.density === "compact") score += 14;
    if (spec.template === "ticker" || spec.template === "ribbon") score += 12;
    if (spec.template === "story" || spec.template === "carousel" || spec.template === "video") score -= 18;
  } else if (opportunity.reservedSize.height >= 260) {
    if (spec.density === "immersive" || spec.template === "poster") score += 10;
    if (spec.template === "ticker") score -= 16;
  }

  return score;
}

function diversityPenalty(layout: AdLayout, context?: LayoutDiversityContext): number {
  if (!context) return 0;

  const spec = layoutRegistry.getSpec(layout);
  const layoutRepeats = context.priorLayoutCounts?.[layout] ?? 0;
  const familyRepeats = context.priorFamilyCounts?.[spec.family] ?? 0;
  const templateRepeats = context.priorTemplateCounts?.[spec.template] ?? 0;

  return layoutRepeats * 16 + familyRepeats * 9 + templateRepeats * 11;
}

function layoutScore(
  layout: AdLayout,
  opportunity: PlacementOpportunity,
  ad: Ad,
  client: ClientHints,
  bandwidthClass: "low" | "normal" | "high",
  layoutHint?: AdLayout,
  diversityContext?: LayoutDiversityContext,
): number {
  const spec = layoutRegistry.getSpec(layout);
  const viewportWidth = client.viewportWidth;

  if (spec.minViewportWidth && viewportWidth && viewportWidth < spec.minViewportWidth) {
    return Number.NEGATIVE_INFINITY;
  }

  let score = 0;

  if (layout === layoutHint) score += 45;
  score += intentScore(opportunity, layout);
  if (opportunity.placementHints.some((placement) => spec.preferredPlacements.includes(placement))) score += 26;
  if (opportunity.surface === "sidebar" && (spec.family === "sidebar" || spec.orientation === "vertical")) score += 24;
  if (opportunity.surface === "article" && (spec.family === "native" || spec.template === "split")) score += 22;
  if (opportunity.surface === "feed" && (spec.family === "carousel" || spec.family === "native" || spec.family === "commerce")) score += 20;
  if (opportunity.surface === "hero" && spec.family === "hero") score += 28;
  if (opportunity.depthBucket === "above_fold" && (spec.family === "hero" || spec.template === "ribbon")) score += 18;
  if (opportunity.depthBucket === "deep" && spec.lowBandwidthSafe) score += 8;

  if (bandwidthClass === "low") {
    score += spec.lowBandwidthSafe ? 40 : -35;
  } else if (bandwidthClass === "high" && (spec.family === "hero" || spec.family === "carousel" || spec.family === "video")) {
    score += 12;
  }

  if (spec.family === "video") {
    score += hasVideo(ad) ? 28 : -100;
  }

  if (spec.family === "carousel") {
    score += ad.creative?.thumbnails?.length ? Math.min(18, ad.creative.thumbnails.length * 4) : -8;
  }

  if (spec.family === "commerce") {
    score += ad.type === "direct" || ad.type === "sponsored" || ad.type === "native" ? 18 : 6;
  }

  if (spec.family === "sponsored") {
    score += ad.type === "direct" || ad.type === "house" ? 16 : 8;
  }

  if (spec.family === "fallback") {
    score += bandwidthClass === "low" ? 14 : -18;
  }

  if (hasImage(ad)) {
    score += spec.template === "poster" || spec.template === "split" || spec.template === "hero" ? 10 : 4;
  } else {
    score += spec.template === "ticker" || spec.template === "interactive" ? 6 : -4;
  }

  if (spec.orientation === "horizontal" && opportunity.surface !== "sidebar") score += 6;
  if (spec.orientation === "square" && opportunity.surface === "sidebar") score += 6;

  return score - diversityPenalty(layout, diversityContext);
}

function pickCompatibleLayout(
  layouts: AdLayout[],
  opportunity: PlacementOpportunity,
  ad: Ad,
  client: ClientHints,
  bandwidthClass: "low" | "normal" | "high",
  layoutHint?: AdLayout,
  diversityContext?: LayoutDiversityContext,
): LayoutSelection {
  const ranked = layouts
    .map((layout) => ({
      layout,
      spec: layoutRegistry.getSpec(layout),
      score: layoutScore(layout, opportunity, ad, client, bandwidthClass, layoutHint, diversityContext),
    }))
    .filter((entry) => Number.isFinite(entry.score))
    .sort((left, right) => right.score - left.score);

  for (const entry of ranked) {
    if (layoutRegistry.supportsAd(entry.layout, ad, bandwidthClass)) {
      return {
        layout: entry.layout,
        spec: entry.spec,
        reason: `scored_layout:${entry.score.toFixed(2)}`,
      };
    }
  }

  const fallbackLayout = bandwidthClass === "low"
    ? "low_bandwidth"
    : opportunity.surface === "sidebar"
      ? "medium_rectangle"
      : opportunity.designIntent === "immersive"
        ? "native_feed"
        : opportunity.designIntent === "compact"
          ? "inline_text"
          : "native_in_content";
  return {
    layout: fallbackLayout,
    spec: layoutRegistry.getSpec(fallbackLayout),
    reason: "fallback_layout",
  };
}

export class LayoutResolver {
  resolve(
    opportunity: PlacementOpportunity,
    ad: Ad,
    client: ClientHints,
    layoutHint?: AdLayout,
    diversityContext?: LayoutDiversityContext,
  ): LayoutSelection {
    const bandwidthClass = getBandwidthClass(client);

    const candidates: AdLayout[] = [];
    if (layoutHint) candidates.push(layoutHint);
    candidates.push(...ad.layouts);
    for (const placementHint of opportunity.placementHints) {
      candidates.push(...layoutRegistry.getPlacementDefaults(placementHint));
    }

    const deduped = Array.from(new Set(candidates));
    return pickCompatibleLayout(deduped, opportunity, ad, client, bandwidthClass, layoutHint, diversityContext);
  }
}
