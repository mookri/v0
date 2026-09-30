import { CampaignCandidate } from '@navdhya/ads/src/decision/Candidate';
import { AdRequest } from '@navdhya/ads/src/contracts/AdRequest';

// Personalized campaigns require consent.personalizedAds; contextual always allowed.
export function checkConsent(c: CampaignCandidate, req: AdRequest): boolean {
  const needsPersonalized = (c.campaign.segmentIds?.length ?? 0) > 0;
  if (needsPersonalized && !req.consent.personalizedAds) return false;
  return true;
}
