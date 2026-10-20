//lib/ads/tracking.ts
export function trackImpression(adId: string) {
    console.log("[Ad Impression]", adId);

    // 🔌 Replace later:
    // fetch("/api/track/impression", { method: "POST", body: JSON.stringify({ adId }) });
}

export function trackClick(adId: string) {
    console.log("[Ad Click]", adId);

    // 🔌 Replace later
}