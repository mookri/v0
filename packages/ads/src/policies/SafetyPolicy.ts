import { CampaignCandidate } from '../decision/Candidate';

export function checkSafety(c: CampaignCandidate): boolean {
  return c.creative.status === 'approved';
}
