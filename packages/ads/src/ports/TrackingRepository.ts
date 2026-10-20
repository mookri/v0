export type AdEventType = 'impression' | 'viewable_impression' | 'click' | 'dismiss' | 'conversion';

export interface AdEvent {
  eventId: string;
  type: AdEventType;
  adId: string;
  campaignId: string;
  placementId: string;
  timestamp: string;
  requestId: string;
}

export interface TrackingRepository {
  record(event: AdEvent): Promise<void>;
}
