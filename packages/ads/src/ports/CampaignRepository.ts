import { Campaign } from '@navdhya/ads/src/domain/campaign/Campaign';

export interface CampaignRepository {
  listActive(): Promise<Campaign[]>;
  findById(id: string): Promise<Campaign | null>;
}
