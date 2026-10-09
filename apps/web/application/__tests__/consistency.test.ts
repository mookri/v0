/**
 * @jest-environment node
 *
 * Consistency-branch gate tests: shared tracking schema (ads) and the
 * unified route guard (`auth/p0-contract.md` §7/§8 statuses).
 */
import { trackingBatchSchema, MAX_TRACKING_EVENTS } from '@/app/api/ads/tracking-schema';
import { postContext, getRequestContext } from '@/server/route-guard';
import { mfaPostContext } from '@/server/mfa-guard';
import { emptyCommandSchema } from '@/application/state-schemas';
import { z } from 'zod';

function req(body: string | null, headers: Record<string, string> = {}): Request {
  return new Request('http://localhost/api/test', {
    method: 'POST',
    body,
    headers: { 'Content-Type': 'application/json', cookie: 'navdhya-session=tok', ...headers },
  });
}

describe('tracking batch schema', () => {
  const validEvent = {
    type: 'impression',
    requestId: 'req',
    decisionId: 'decision',
    auctionId: 'auction',
    adId: 'navdhya-house-booking',
    placement: 'article_inline_1',
    layout: 'inline_text',
    timestamp: Date.now(),
    idempotencyKey: 'decision:impression',
    experimentAssignments: {},
  };
  const validBatch = {
    batchId: 'batch_1',
    sentAt: Date.now(),
    source: 'fetch',
    events: [validEvent],
  };

  test('accepts the payload shape the existing route tests send', () => {
    expect(trackingBatchSchema.safeParse(validBatch).success).toBe(true);
  });

  test('rejects the old ad-hoc invalid cases (batchId/events)', () => {
    expect(trackingBatchSchema.safeParse({ ...validBatch, batchId: '' }).success).toBe(false);
    expect(trackingBatchSchema.safeParse({ ...validBatch, events: 'nope' }).success).toBe(false);
  });

  test('rejects events missing identifiers (used to surface as 500s)', () => {
    const { adId: _adId, ...withoutAdId } = validEvent;
    expect(trackingBatchSchema.safeParse({ ...validBatch, events: [withoutAdId] }).success).toBe(false);
    expect(trackingBatchSchema.safeParse({ ...validBatch, events: [{ type: 'x' }] }).success).toBe(false);
  });

  test('bounds batch size and rejects unknown sources', () => {
    const tooMany = Array.from({ length: MAX_TRACKING_EVENTS + 1 }, (_, i) => ({
      ...validEvent,
      idempotencyKey: `key-${i}`,
    }));
    expect(trackingBatchSchema.safeParse({ ...validBatch, events: tooMany }).success).toBe(false);
    expect(trackingBatchSchema.safeParse({ ...validBatch, source: 'evil' }).success).toBe(false);
  });
});

describe('shared route guard (§7/§8 statuses)', () => {
  const savedEnv = {
    CUSTOMER_BACKEND: process.env.CUSTOMER_BACKEND,
    NAVDHYA_API_BASE: process.env.NAVDHYA_API_BASE,
  };

  afterEach(() => {
    for (const [key, value] of Object.entries(savedEnv)) {
      if (value === undefined) delete process.env[key];
      else process.env[key] = value;
    }
  });

  test('backend not wired → 401', async () => {
    delete process.env.CUSTOMER_BACKEND;
    const ctx = await postContext(req('{}'), 'rid', { schema: emptyCommandSchema });
    expect(ctx.ok).toBe(false);
    if (!ctx.ok) expect(ctx.response.status).toBe(401);
  });

  test('malformed JSON → 400 (§8)', async () => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://localhost';
    const ctx = await postContext(req('not-json'), 'rid', { schema: emptyCommandSchema });
    expect(ctx.ok).toBe(false);
    if (!ctx.ok) expect(ctx.response.status).toBe(400);
  });

  test('schema failure → 422; oversize content-length → 413 (§8)', async () => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://localhost';

    const schemaFail = await postContext(
      req(JSON.stringify({ ref_id: '../evil' })),
      'rid',
      { schema: z.object({ ref_id: z.string().regex(/^[A-Za-z0-9_-]+$/) }) },
    );
    expect(schemaFail.ok).toBe(false);
    if (!schemaFail.ok) expect(schemaFail.response.status).toBe(422);

    const oversize = await postContext(
      req('{}', { 'content-length': String(2 * 1024 * 1024) }),
      'rid',
      { schema: emptyCommandSchema },
    );
    expect(oversize.ok).toBe(false);
    if (!oversize.ok) expect(oversize.response.status).toBe(413);
  });

  test('empty body accepted only when allowEmptyBody (bodyless commands)', async () => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://localhost';

    const allowed = await postContext(req(null), 'rid', {
      schema: emptyCommandSchema,
      allowEmptyBody: true,
    });
    expect(allowed.ok).toBe(true);

    const required = await postContext(req(null), 'rid', { schema: emptyCommandSchema });
    expect(required.ok).toBe(false);
    if (!required.ok) expect(required.response.status).toBe(400);
  });

  test('throttle denies with 429 + Retry-After (§3)', async () => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://localhost';

    const options = {
      schema: emptyCommandSchema,
      throttle: { purpose: 'unit-guard', limit: 1, windowSeconds: 60 },
    };
    const first = await postContext(req('{}'), 'rid', options);
    expect(first.ok).toBe(true);

    const second = await postContext(req('{}'), 'rid', options);
    expect(second.ok).toBe(false);
    if (!second.ok) {
      expect(second.response.status).toBe(429);
      expect(second.response.headers.get('Retry-After')).toBeTruthy();
    }
  });

  test('happy path: cookie actor + parsed data (§7 row 1)', async () => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://localhost';

    const ctx = await postContext(
      req(JSON.stringify({ value: 7 })),
      'rid',
      { schema: z.object({ value: z.number() }) },
    );
    expect(ctx.ok).toBe(true);
    if (ctx.ok) {
      expect(ctx.actor.source).toBe('cookie');
      expect(ctx.data).toEqual({ value: 7 });
    }

    const get = await getRequestContext(
      new Request('http://localhost/api/test', {
        headers: { cookie: 'navdhya-session=tok' },
      }),
      'rid',
    );
    expect(get.ok).toBe(true);
  });

  test('mfa wrapper delegates with the MFA throttle policy pinned', async () => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://localhost';

    const ctx = await mfaPostContext(req(JSON.stringify({ code: '123456' })), 'rid', z.object({ code: z.string() }));
    expect(ctx.ok).toBe(true);

    // Disabled backend still short-circuits through the shared guard.
    delete process.env.CUSTOMER_BACKEND;
    const denied = await mfaPostContext(req('{}'), 'rid', emptyCommandSchema);
    expect(denied.ok).toBe(false);
    if (!denied.ok) expect(denied.response.status).toBe(401);
  });
});

describe('backend error-code map (laravel-contract §4)', () => {
  test('step_up_required maps to FORBIDDEN, not opaque INTERNAL_ERROR (M9)', async () => {
    const { mapBackendCode } = await import('@/adapters/backend/laravel-client');
    expect(mapBackendCode('step_up_required')).toBe('FORBIDDEN');
  });

  test('known auth-shaped codes map as documented', async () => {
    const { mapBackendCode } = await import('@/adapters/backend/laravel-client');
    expect(mapBackendCode('unauthenticated')).toBe('UNAUTHENTICATED');
    expect(mapBackendCode('rate_limited')).toBe('RATE_LIMITED');
    expect(mapBackendCode('conflict')).toBe('CONFLICT');
    expect(mapBackendCode('validation_failed')).toBe('VALIDATION_ERROR');
    // Unmapped codes stay deliberately opaque.
    expect(mapBackendCode('some_future_code')).toBe('INTERNAL_ERROR');
  });
});
