import { processTrackingRequest } from '@/app/api/ads/tracking-handler';

/**
 * POST /api/ads/track-beacon — unload-safe tracking batch.
 * Shared validation/throttle pipeline: `@/app/api/ads/tracking-handler.ts`.
 */
export async function POST(request: Request) {
  return processTrackingRequest(request, 'Failed to record beacon tracking batch');
}
