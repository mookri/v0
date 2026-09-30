import { AdRequest } from '../contracts/AdRequest';
import { CampaignCandidate } from '../decision/Candidate';

export interface CandidateSource {
  findCandidates(req: AdRequest): Promise<CampaignCandidate[]>;
}
