import { processTrackingRequest } from '@/app/api/ads/tracking-handler';

/**
 * POST /api/ads/track — record a tracking batch.
 * Shared validation/throttle pipeline: `@/app/api/ads/tracking-handler.ts`.
 */
export async function POST(request: Request) {
  return processTrackingRequest(request, 'Failed to record tracking batch');
}
