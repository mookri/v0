import { InProcessRateLimiter, clientIp } from '@/lib/security/rate-limit';
import { getAdOrchestrationRuntime } from '@/lib/ad-orchestration';
import type { TrackingBatchPayload } from '@/lib/ad-orchestration';
import { trackingBatchSchema, MAX_TRACKING_BODY_BYTES } from './tracking-schema';

/**
 * Shared handler for `/api/ads/track` and `/api/ads/track-beacon`.
 *
 * Order: 64 KB cap → JSON parse (400) → zod batch schema (400, same
 * "Invalid tracking payload" contract as before) → per-IP batch
 * throttle (429 + Retry-After) → record + side effects.
 *
 * CSRF is deliberately NOT enforced here: unlike the decision route,
 * no client sends `x-csrf-token` on tracking calls (verified — the
 * only reader in the repo is the decision route), so gating tracking
 * would silently drop impression/click accounting wherever the gate
 * activates. Tracking is unauthenticated by design and bounded instead.
 */

/** 60 batches/min/IP; each batch carries up to 50 events. */
const trackLimiter = new InProcessRateLimiter(
  { limit: 60, windowMs: 60_000 },
  { maxKeys: 50_000 },
);

export async function processTrackingRequest(
  request: Request,
  failureMessage: string,
): Promise<Response> {
  const contentLength = request.headers.get('content-length');
  if (contentLength) {
    const n = Number(contentLength);
    if (Number.isFinite(n) && n > MAX_TRACKING_BODY_BYTES) {
      return Response.json({ error: 'Request body too large' }, { status: 413 });
    }
  }

  let raw: unknown;
  try {
    raw = await request.json();
  } catch {
    return Response.json({ error: 'Invalid JSON body' }, { status: 400 });
  }

  const parsed = trackingBatchSchema.safeParse(raw);
  if (!parsed.success) {
    // Same status + message the ad-hoc check used to produce.
    return Response.json({ error: 'Invalid tracking payload' }, { status: 400 });
  }

  const decision = trackLimiter.check(clientIp(request.headers), 1);
  if (!decision.allowed) {
    const retryAfter = Math.max(1, Math.ceil((decision.resetAt - Date.now()) / 1000));
    return Response.json(
      { error: 'Too many tracking requests' },
      { status: 429, headers: { 'Retry-After': String(retryAfter) } },
    );
  }

  try {
    const payload = parsed.data as TrackingBatchPayload;
    const runtime = getAdOrchestrationRuntime();
    const normalizedBatch: TrackingBatchPayload = {
      ...payload,
      events: payload.events.map((event) => ({
        ...event,
        timestamp: event.timestamp || Date.now(),
        customData: {
          ...event.customData,
          serverTimestamp: Date.now(),
        },
      })),
    };

    const result = await runtime.eventSink.record(normalizedBatch);

    for (const event of normalizedBatch.events) {
      runtime.experimentEngine.record(event);

      const ad = await runtime.repository.getById(event.adId);
      const revenue = Number(event.customData?.revenue ?? event.customData?.clearingPrice ?? 0);
      if (ad && revenue > 0 && (event.type === 'impression' || event.type === 'click' || event.type === 'viewable')) {
        await runtime.budgetLedger.addSpend(ad.id, revenue);
      }

      if (event.type === 'impression') {
        await runtime.frequencyStore.increment(`tracked:${event.adId}:${event.placement}`, 24 * 60 * 60 * 1000);
      }
    }

    return Response.json({ ok: true, accepted: result.accepted, duplicates: result.duplicates });
  } catch (error) {
    return Response.json(
      { error: error instanceof Error ? error.message : failureMessage },
      { status: 500 },
    );
  }
}
