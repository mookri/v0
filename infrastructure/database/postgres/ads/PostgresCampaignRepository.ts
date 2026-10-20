import type { Campaign } from '@infra/packages/ads/src/domain/campaign/Campaign';
import type { CampaignRepository } from '@infra/packages/ads/src/ports/CampaignRepository';

// Owns: campaigns, campaign_flights, campaign_targeting.
export class PostgresCampaignRepository implements CampaignRepository {
  private readonly store = new Map<string, Campaign>();

  seed(campaigns: Campaign[]): void {
    for (const c of campaigns) this.store.set(c.id, c);
  }

  async listActive(): Promise<Campaign[]> {
    return [...this.store.values()].filter((c) => c.status === 'active');
  }

  async findById(id: string): Promise<Campaign | null> {
    return this.store.get(id) ?? null;
  }
}
