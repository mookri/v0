import { Campaign } from '@navdhya/ads/src/domain/campaign/Campaign';

export interface ProductAdPolicy {
  product: string;
  allowedPlacements: string[];
}

export function isPlacementAllowed(policy: ProductAdPolicy, placementId: string): boolean {
  return policy.allowedPlacements.includes(placementId);
}

export function filterByProductPolicy(campaigns: Campaign[], policy: ProductAdPolicy): Campaign[] {
  return campaigns.filter((c) => c.placementIds.some((p) => isPlacementAllowed(policy, p)));
}
