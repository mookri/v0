export type AttributionSource = 'search' | 'recommendation' | 'ads' | 'editorial' | 'direct' | 'communication';

export interface AttributionContext {
  source: AttributionSource;
  contextId?: string;
  campaignId?: string;
  queryId?: string;
  resultId?: string;
  position?: number;
}

export function joinAttribution(contexts: AttributionContext[]): AttributionContext | null {
  return contexts[0] ?? null;
}
