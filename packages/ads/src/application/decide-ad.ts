import { AdRequest } from '../contracts/AdRequest';
import { AdDecision } from '../contracts/AdDecision';
import { CandidateSource } from '../ports/CandidateSource';
import { decide } from '../decision/decision-engine/decide';

export async function decideAd(
  source: CandidateSource,
  req: AdRequest,
  placementFormats: string[],
): Promise<AdDecision> {
  const candidates = await source.findCandidates(req);
  return decide(req, candidates, { placementFormats });
}
