import { Campaign } from '@navdhya/ads/src/domain/campaign/Campaign';
import { Creative } from '@navdhya/ads/src/domain/creative/Creative';

export interface CampaignCandidate {
  campaign: Campaign;
  creative: Creative;
}
