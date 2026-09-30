import { AdRequest } from '../../contracts/AdRequest';
import { AdDecision } from '../../contracts/AdDecision';
import { CampaignCandidate } from '../Candidate';
import { checkEligibility } from '../eligibility/checkEligibility';
import { rankCandidates } from '../ranking/rankCandidates';
import { runAuction } from '../auction/runAuction';

export interface DecisionDeps {
  placementFormats: string[];
}

export function decide(
  req: AdRequest,
  candidates: CampaignCandidate[],
  deps: DecisionDeps,
): AdDecision {
  const trackingBase = {
    requestId: req.requestId,
    decisionId: `${req.placementId}:${req.requestId}`,
    impressionKey: `${req.requestId}:${req.placementId}:impression`,
    clickKey: `${req.requestId}:${req.placementId}:click`,
  };
  const { eligible } = checkEligibility(candidates, req, deps.placementFormats);
  if (eligible.length === 0) {
    return {
      decisionId: trackingBase.decisionId,
      placementId: req.placementId,
      status: 'empty',
      reason: 'NO_ELIGIBLE_CAMPAIGN',
      renderer: 'empty',
      tracking: trackingBase,
    };
  }
  const winner = runAuction(rankCandidates(eligible));
  if (!winner) {
    return {
      decisionId: trackingBase.decisionId,
      placementId: req.placementId,
      status: 'empty',
      reason: 'AUCTION_EMPTY',
      renderer: 'empty',
      tracking: trackingBase,
    };
  }
  const { campaign, creative } = winner.candidate;
  return {
    decisionId: trackingBase.decisionId,
    placementId: req.placementId,
    status: 'filled',
    campaign: { id: campaign.id, source: campaign.source, priority: campaign.priority },
    creative: {
      id: creative.id,
      format: creative.format,
      headline: creative.headline,
      ctaText: creative.ctaText,
      destination: creative.destination,
      isSponsored: true,
    },
    renderer: creative.format,
    tracking: trackingBase,
  };
}
