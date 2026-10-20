import { Creative } from '@navdhya/ads/src/domain/creative/Creative';
import { Campaign } from '@navdhya/ads/src/domain/campaign/Campaign';

export interface TrackingContext {
  requestId: string;
  decisionId: string;
  impressionKey: string;
  clickKey: string;
}

export type AdDecision =
  | {
      decisionId: string;
      placementId: string;
      status: 'filled';
      campaign: Pick<Campaign, 'id' | 'source' | 'priority'>;
      creative: Pick<Creative, 'id' | 'format' | 'headline' | 'ctaText' | 'destination' | 'isSponsored'>;
      renderer: string;
      tracking: TrackingContext;
    }
  | {
      decisionId: string;
      placementId: string;
      status: 'empty';
      reason: string;
      renderer: string;
      tracking: TrackingContext;
    };
