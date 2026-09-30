import { ScoredCandidate } from '@navdhya/ads/src/decision/ranking/rankCandidates';

// First-price, deterministic: highest score wins. Ties break by campaign id.
export function runAuction(ranked: ScoredCandidate[]): ScoredCandidate | null {
  if (ranked.length === 0) return null;
  const top = ranked[0].score;
  const tied = ranked.filter((r) => r.score === top).sort((a, b) => a.candidate.campaign.id.localeCompare(b.candidate.campaign.id));
  return tied[0];
}
