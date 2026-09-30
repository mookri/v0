import type { AdRequest } from '../../../../packages/ads/src/contracts/AdRequest';

// Translates Next.js transport concerns into a normalized AdRequest.
// Ads core never sees NextRequest, cookies, or DB records.
export function toAdRequest(input: {
  requestId: string;
  placementId: string;
  routeId: string;
  locale?: string;
  personalizedAds?: boolean;
  segmentIds?: string[];
}): AdRequest {
  return {
    requestId: input.requestId,
    product: 'web',
    placementId: input.placementId,
    pageContext: { routeId: input.routeId },
    locale: input.locale ?? 'en',
    consent: { personalizedAds: input.personalizedAds ?? false, measurement: true },
    audience: { authenticated: false, segmentIds: input.segmentIds },
    timestamp: new Date().toISOString(),
  };
}

export const rendererComponents = {
  native: 'NativeAds',
  image: 'RectangularAds',
  hero: 'HeroAd',
  'sponsored-card': 'SponsoredAds',
  rectangle: 'RectangularAds',
  vertical: 'VerticalAds',
  text: 'NativeAds',
  video: 'NativeAds',
  empty: 'EmptySlot',
} as const;
