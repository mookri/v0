import type { PricingModel } from "@/components/ad-network/core/types";
import type { AuctionCandidate, AuctionOutcome, PlacementOpportunity, RankedCandidate } from "./types";

function priorityTierFor(model: PricingModel | undefined): AuctionCandidate["priorityTier"] {
  if (model === "programmatic_guaranteed_cpm" || model === "guaranteed") {
    return "guaranteed";
  }
  if (model === "preferred_deal_cpm" || model === "preferred_deal") {
    return "preferred";
  }
  return "auction";
}

function isSecondPrice(model: PricingModel | undefined): boolean {
  return model === "second_price_auction";
}

function dynamicFloorBase(opportunity: PlacementOpportunity): number {
  if (opportunity.surface === "hero") return 750;
  if (opportunity.surface === "sidebar") return 320;
  if (opportunity.surface === "feed") return 230;
  return 240;
}

function slotQualityMultiplier(opportunity: PlacementOpportunity): number {
  if (opportunity.depthBucket === "above_fold") return 1.2;
  if (opportunity.depthBucket === "mid") return 1.0;
  return 0.86;
}

function demandMultiplier(candidateCount: number): number {
  return Math.min(1.4, 1 + Math.max(0, candidateCount - 1) * 0.07);
}

function viewabilityMultiplier(opportunity: PlacementOpportunity): number {
  if (opportunity.depthBucket === "above_fold") return 1.15;
  if (opportunity.depthBucket === "mid") return 1.0;
  return 0.9;
}

function shadeBid(candidate: RankedCandidate, floor: number): number {
  const alpha = candidate.ad.budget?.bidShadeAlpha ?? 0.68;
  return floor + Math.max(0, candidate.revenueEcpm - floor) * alpha;
}

function buildCandidates(rankedCandidates: RankedCandidate[], floor: number): AuctionCandidate[] {
  return rankedCandidates.map((ranked) => {
    const pricingModel = ranked.ad.budget?.model;
    const priorityTier = priorityTierFor(pricingModel);
    const shadedBid = shadeBid(ranked, floor);

    return {
      ranked,
      pricingModel,
      effectiveBid: ranked.revenueEcpm,
      shadedBid,
      floor,
      priorityTier,
    };
  });
}

export class AuctionEngine {
  run(rankedCandidates: RankedCandidate[], opportunity: PlacementOpportunity): AuctionOutcome {
    if (rankedCandidates.length === 0) {
      return {
        winner: null,
        auctionId: `auc_${Date.now().toString(36)}`,
        clearingPrice: 0,
        floorApplied: 0,
        auctionType: "none",
        debug: { candidates: [] },
      };
    }

    const floorApplied =
      dynamicFloorBase(opportunity) *
      slotQualityMultiplier(opportunity) *
      demandMultiplier(rankedCandidates.length) *
      viewabilityMultiplier(opportunity);

    const candidates = buildCandidates(rankedCandidates, floorApplied)
      .filter((candidate) => candidate.effectiveBid >= floorApplied * 0.85)
      .sort((left, right) => {
        const priorityOrder = { guaranteed: 3, preferred: 2, auction: 1 };
        const priorityDiff = priorityOrder[right.priorityTier] - priorityOrder[left.priorityTier];
        if (priorityDiff !== 0) return priorityDiff;
        if (right.effectiveBid !== left.effectiveBid) return right.effectiveBid - left.effectiveBid;
        return right.ranked.totalScore - left.ranked.totalScore;
      });

    const winnerCandidate = candidates[0];
    if (!winnerCandidate) {
      return {
        winner: null,
        auctionId: `auc_${Date.now().toString(36)}`,
        clearingPrice: 0,
        floorApplied,
        auctionType: "none",
        debug: { candidates: [] },
      };
    }

    const secondCandidate = candidates[1];
    const auctionType =
      winnerCandidate.priorityTier === "guaranteed"
        ? "guaranteed"
        : winnerCandidate.priorityTier === "preferred"
          ? "preferred"
          : isSecondPrice(winnerCandidate.pricingModel)
            ? "second_price"
            : winnerCandidate.pricingModel === "hybrid_cpm_cpc" || winnerCandidate.pricingModel === "hybrid_cpa_cpv"
              ? "hybrid"
              : "first_price";

    let clearingPrice = winnerCandidate.shadedBid;
    if (auctionType === "guaranteed" || auctionType === "preferred") {
      clearingPrice = Math.max(floorApplied, winnerCandidate.effectiveBid);
    } else if (auctionType === "second_price") {
      const epsilon = 0.01;
      clearingPrice = Math.max(floorApplied, (secondCandidate?.effectiveBid ?? floorApplied) + epsilon);
    } else {
      clearingPrice = Math.max(floorApplied, winnerCandidate.shadedBid);
    }

    return {
      winner: winnerCandidate.ranked,
      auctionId: `auc_${Date.now().toString(36)}`,
      clearingPrice,
      floorApplied,
      auctionType,
      debug: {
        candidates: candidates.map((candidate) => ({
          adId: candidate.ranked.ad.id,
          effectiveBid: candidate.effectiveBid,
          shadedBid: candidate.shadedBid,
          totalScore: candidate.ranked.totalScore,
          pricingModel: candidate.pricingModel,
          priorityTier: candidate.priorityTier,
        })),
      },
    };
  }
}

