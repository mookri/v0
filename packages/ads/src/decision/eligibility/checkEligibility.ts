import { AdRequest } from '@navdhya/ads/src/contracts/AdRequest';
import { CampaignCandidate } from '@navdhya/ads/src/decision/Candidate';
import { isCampaignActive } from '@navdhya/ads/src/domain/campaign/campaign-rules';
import { isCreativeServable } from '@navdhya/ads/src/domain/creative/creative-rules';
import { checkConsent } from '@navdhya/ads/src/policies/ConsentPolicy';
import { checkSafety } from '@navdhya/ads/src/policies/SafetyPolicy';

export interface EligibilityResult {
  eligible: CampaignCandidate[];
  rejected: { id: string; reason: string }[];
}

export function checkEligibility(
  candidates: CampaignCandidate[],
  req: AdRequest,
  placementFormats: string[],
): EligibilityResult {
  const eligible: CampaignCandidate[] = [];
  const rejected: { id: string; reason: string }[] = [];
  for (const c of candidates) {
    if (!isCampaignActive(c.campaign, req.timestamp)) {
      rejected.push({ id: c.campaign.id, reason: 'INACTIVE' });
      continue;
    }
    if (!c.campaign.placementIds.includes(req.placementId)) {
      rejected.push({ id: c.campaign.id, reason: 'PLACEMENT' });
      continue;
    }
    if (!placementFormats.includes(c.creative.format)) {
      rejected.push({ id: c.campaign.id, reason: 'FORMAT' });
      continue;
    }
    if (!isCreativeServable(c.creative)) {
      rejected.push({ id: c.campaign.id, reason: 'CREATIVE' });
      continue;
    }
    if (!checkConsent(c, req)) {
      rejected.push({ id: c.campaign.id, reason: 'CONSENT' });
      continue;
    }
    if (!checkSafety(c)) {
      rejected.push({ id: c.campaign.id, reason: 'SAFETY' });
      continue;
    }
    eligible.push(c);
  }
  return { eligible, rejected };
}
