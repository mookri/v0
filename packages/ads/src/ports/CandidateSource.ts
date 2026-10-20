import { AdRequest } from '@navdhya/ads/src/contracts/AdRequest';
import { CampaignCandidate } from '@navdhya/ads/src/decision/Candidate';

export interface CandidateSource {
  findCandidates(req: AdRequest): Promise<CampaignCandidate[]>;
}
