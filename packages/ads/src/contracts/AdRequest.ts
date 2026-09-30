export interface PageContext {
  routeId: string;
  resourceType?: string;
  resourceId?: string;
  category?: string;
  contentType?: string;
}

export interface AudienceContext {
  authenticated: boolean;
  segmentIds?: string[];
  eligibilityFlags?: string[];
}

export interface ConsentContext {
  personalizedAds: boolean;
  measurement: boolean;
}

export interface AdRequest {
  requestId: string;
  product: 'web' | 'customer' | 'partner' | 'admin';
  placementId: string;
  pageContext: PageContext;
  locale: string;
  consent: ConsentContext;
  audience?: AudienceContext;
  experiment?: { assignments: Record<string, string> };
  timestamp: string;
}
