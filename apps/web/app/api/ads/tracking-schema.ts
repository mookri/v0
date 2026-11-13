import { z } from 'zod';

/**
 * Tracking batch schemas for `/api/ads/track` and `/api/ads/track-beacon`.
 *
 * These endpoints are unauthenticated by design (beacons fire on page
 * unload), so the schema is what bounds them: capped batch size, capped
 * field lengths, and required identifiers — malformed events used to
 * fall through to the runtime and surface as 500s.
 */

/** A single page render never produces more than this many events. */
export const MAX_TRACKING_EVENTS = 50;

/** Cap on the body size (bytes). Matches the decision route. */
export const MAX_TRACKING_BODY_BYTES = 64 * 1024;

const FORBIDDEN_ANALYTICS_PII_KEYS = new Set([
  'email',
  'phone',
  'mobile',
  'pan',
  'aadhaar',
  'address',
  'gotra',
  'nakshatra',
  'bank_account',
  'ifsc',
  'name',
  'full_name',
]);

const trackingEventSchema = z.object({
  type: z.string().trim().min(1).max(40),
  requestId: z.string().max(200),
  decisionId: z.string().max(200),
  auctionId: z.string().max(200),
  adId: z.string().trim().min(1).max(128),
  creativeId: z.string().max(200).optional(),
  placement: z.string().trim().min(1).max(128),
  layout: z.string().trim().min(1).max(128),
  timestamp: z.number().optional(),
  batchId: z.string().max(128).optional(),
  idempotencyKey: z.string().max(256),
  experimentAssignments: z
    .record(z.string().max(128), z.string().max(128))
    .optional()
    .default({}),
  scrollDepth: z.number().min(0).max(1).optional(),
  viewabilityRatio: z.number().min(0).max(1).optional(),
  viewabilityMs: z.number().min(0).max(600_000).optional(),
  connectionType: z.string().max(32).optional(),
  customData: z
    .record(z.string().max(64), z.unknown())
    .refine(
      (record) => Object.keys(record).every((k) => !FORBIDDEN_ANALYTICS_PII_KEYS.has(k.toLowerCase())),
      { message: 'Analytics customData must not contain PII keys' },
    )
    .optional(),
});

export const trackingBatchSchema = z.object({
  batchId: z.string().trim().min(1).max(128),
  events: z.array(trackingEventSchema).max(MAX_TRACKING_EVENTS),
  sentAt: z.number(),
  source: z.enum(['queue', 'beacon', 'fetch']),
});

export type TrackingBatchInput = z.infer<typeof trackingBatchSchema>;
