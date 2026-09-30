import { CampaignCandidate } from '../Candidate';

export interface ScoredCandidate {
  candidate: CampaignCandidate;
  score: number;
}

export function rankCandidates(candidates: CampaignCandidate[]): ScoredCandidate[] {
  return candidates
    .map((candidate) => ({
      candidate,
      score: candidate.campaign.bid * 1000 + candidate.campaign.priority,
    }))
    .sort((a, b) => b.score - a.score);
}
