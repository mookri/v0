import type { Ad } from "@/components/ad-network/core/types";
import type { CampaignRepository, DecisionRequest, PlacementOpportunity } from "./types";
import { CANONICAL_CAMPAIGN_SEED } from "./campaign-seed";

export class SeedCampaignRepository implements CampaignRepository {
  private readonly campaigns: Ad[];

  constructor(seed: Ad[] = CANONICAL_CAMPAIGN_SEED) {
    this.campaigns = seed;
  }

  async listCandidates(_request: DecisionRequest, opportunity: PlacementOpportunity): Promise<Ad[]> {
    return this.campaigns.filter((ad) => {
      if (ad.status && ad.status !== "active" && ad.status !== "scheduled") {
        return false;
      }

      return opportunity.placementHints.some((placement) => ad.placements.includes(placement));
    });
  }

  async getById(adId: string): Promise<Ad | null> {
    return this.campaigns.find((ad) => ad.id === adId) ?? null;
  }

  async getAll(): Promise<Ad[]> {
    return [...this.campaigns];
  }
}

