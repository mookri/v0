import { CampaignId } from './CampaignId';
import { CampaignStatus } from './CampaignStatus';

export type CampaignSource = 'internal' | 'partner' | 'external';

export interface Campaign {
  id: CampaignId;
  source: CampaignSource;
  status: CampaignStatus;
  priority: number;
  bid: number;
  placementIds: string[];
  formats: string[];
  startAt?: string;
  endAt?: string;
  budgetTotal?: number;
  budgetSpent?: number;
  segmentIds?: string[];
}
