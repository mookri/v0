import { Campaign } from '../domain/campaign/Campaign';

export interface CampaignRepository {
  listActive(): Promise<Campaign[]>;
  findById(id: string): Promise<Campaign | null>;
}
