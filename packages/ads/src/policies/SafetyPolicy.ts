import { CampaignCandidate } from '@navdhya/ads/src/decision/Candidate';

export function checkSafety(c: CampaignCandidate): boolean {
  return c.creative.status === 'approved';
}
