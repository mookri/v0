import { Campaign } from '../domain/campaign/Campaign';
import { Creative } from '../domain/creative/Creative';

export interface CampaignCandidate {
  campaign: Campaign;
  creative: Creative;
}
