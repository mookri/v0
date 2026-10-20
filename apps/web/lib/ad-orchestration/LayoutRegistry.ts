import type { Ad, AdLayout, AdPlacement } from "@/components/ad-network/core/types";
import { ALL_LAYOUTS, ALL_PLACEMENTS, getLayoutSpec, getPlacementDefaults } from "./layout-catalog";
import type { LayoutSpec } from "./types";

function hasRenderableMedia(ad: Ad): boolean {
  return Boolean(
    ad.creative?.imageUrl ||
    ad.creative?.imageUrlWebp ||
    ad.imageUrl ||
    ad.creative?.videoPosterUrl ||
    ad.creative?.thumbnails?.length,
  );
}

function hasRenderableVideo(ad: Ad): boolean {
  return Boolean(ad.creative?.videoUrl || ad.creative?.videoUrlHls);
}

export class LayoutRegistry {
  getSpec(layout: AdLayout, placementHint?: AdPlacement): LayoutSpec {
    return getLayoutSpec(layout, placementHint);
  }

  getPlacementDefaults(placement: AdPlacement): AdLayout[] {
    return getPlacementDefaults(placement);
  }

  getAllLayouts(): readonly AdLayout[] {
    return ALL_LAYOUTS;
  }

  getAllPlacements(): readonly AdPlacement[] {
    return ALL_PLACEMENTS;
  }

  supportsAd(layout: AdLayout, ad: Ad, bandwidthClass: "low" | "normal" | "high"): boolean {
    const spec = this.getSpec(layout);
    if (spec.requiresVideo && !hasRenderableVideo(ad)) return false;
    if (spec.requiresImage && !hasRenderableMedia(ad)) return false;
    if (bandwidthClass === "low" && !spec.lowBandwidthSafe && spec.template !== "hero" && spec.template !== "poster") {
      return false;
    }
    return true;
  }
}

export const layoutRegistry = new LayoutRegistry();
