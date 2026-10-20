import { AdRequest } from '@navdhya/ads/src/contracts/AdRequest';
import { AdDecision } from '@navdhya/ads/src/contracts/AdDecision';
import { CandidateSource } from '@navdhya/ads/src/ports/CandidateSource';
import { decide } from '@navdhya/ads/src/decision/decision-engine/decide';

export async function decideAd(
  source: CandidateSource,
  req: AdRequest,
  placementFormats: string[],
): Promise<AdDecision> {
  const candidates = await source.findCandidates(req);
  return decide(req, candidates, { placementFormats });
}
