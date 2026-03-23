//lib/ads/engine.ts
import { ADS } from "./registry";
import { Ad, AdPlacement } from "./types";

/* weighted random */
function pickWeighted(ads: Ad[]): Ad | null {
    if (!ads.length) return null;

    const total = ads.reduce((sum, ad) => sum + (ad.weight || 1), 0);
    const rand = Math.random() * total;

    let acc = 0;
    for (const ad of ads) {
        acc += ad.weight || 1;
        if (rand <= acc) return ad;
    }

    return ads[0];
}

/* placement resolver */
export function getAdForPlacement(placement: AdPlacement): Ad | null {
    const eligible = ADS.filter(ad => ad.placements.includes(placement));

    return pickWeighted(eligible);
}