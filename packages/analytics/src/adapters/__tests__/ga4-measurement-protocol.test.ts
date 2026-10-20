// GA4 MP adapter units (plan B4): encoded usage rules — server_ga4-only,
// consent fail-closed, client context never invented, 2xx = receipt only.

import { describe, expect, it } from 'vitest';
import { newEventId, type SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { EVENT_DEFS } from '@navdhya/analytics/src/registry/generated/event-types';
import {
  MP_LIMITS,
  MP_WIRE_NAME,
  buildMpBatch,
  defaultMpClientIdResolver,
  mpResponseOutcome,
  sendMpBatch,
  validateMpPayload,
} from '@navdhya/analytics/src/adapters/ga4-measurement-protocol';

const CLIENT = 'hmac_uid_9f2a';

function serverEnvelope(
  name: 'payment_succeeded' | 'booking_confirmed' | 'payment_failed',
  overrides: Partial<SignalEnvelope> = {},
): SignalEnvelope {
  const params: Record<string, string | number | boolean> =
    name === 'payment_succeeded'
      ? { value: 1499, currency: 'INR', transaction_ref: 'TX_1' }
      : name === 'payment_failed'
        ? { value: 1499, currency: 'INR', transaction_ref: 'TX_1' }
        : { value: 1499, currency: 'INR', service_category: 'puja' };
  return {
    id: newEventId(),
    name,
    version: 1,
    occurredAt: '2026-10-12T10:00:00.000Z',
    surface: 'web',
    appVersion: 'server',
    session: { id: `srv_${name}_agg_1`, seq: 1 },
    actor: { uidHash: null, anonId: null },
    consent: { analytics: true, ads: false },
    context: { routeTemplate: '/' },
    params,
    ...overrides,
  };
}

describe('ga4-measurement-protocol (B4)', () => {
  it('builds a governed batch: purchase (server) + booking_confirmed, sessionized', () => {
    const batch = buildMpBatch(
      [serverEnvelope('payment_succeeded'), serverEnvelope('booking_confirmed')],
      { resolveClientId: () => CLIENT },
    );
    expect(batch.client_id).toBe(CLIENT);
    expect(batch.events.map((e) => e.name)).toEqual(['purchase', 'booking_confirmed']);
    const purchase = batch.events[0]!;
    expect(purchase.params).toMatchObject({
      value: 1499,
      currency: 'INR',
      transaction_ref: 'TX_1',
      engagement_time_msec: '1',
    });
    expect(purchase.params['session_id']).toContain('srv_payment_succeeded');
  });

  it('rejects events that are not server_ga4-eligible (one destination, one path)', () => {
    expect(() =>
      buildMpBatch(
        [{ ...serverEnvelope('payment_succeeded'), name: 'page_view', params: { page_type: 'checkout' } } as SignalEnvelope],
        { resolveClientId: () => CLIENT },
      ),
    ).toThrow(/not server_ga4-eligible/);
    expect(() =>
      buildMpBatch([{ ...serverEnvelope('payment_succeeded'), name: 'totally_bogus' } as SignalEnvelope], {
        resolveClientId: () => CLIENT,
      }),
    ).toThrow(/not server_ga4-eligible|unregistered/);
  });

  it('fails closed without analytics consent (GA4 is external)', () => {
    expect(() =>
      buildMpBatch(
        [serverEnvelope('payment_succeeded', { consent: { analytics: false, ads: false } })],
        { resolveClientId: () => CLIENT },
      ),
    ).toThrow(/lacks analytics consent/);
  });

  it('never invents client context and never mixes identities', () => {
    expect(() =>
      buildMpBatch([serverEnvelope('payment_succeeded')], { resolveClientId: () => null }),
    ).toThrow(/no client context/);
    expect(() =>
      buildMpBatch([serverEnvelope('payment_succeeded'), serverEnvelope('booking_confirmed')], {
        resolveClientId: (envelope) => (envelope.name === 'payment_succeeded' ? 'a' : 'b'),
      }),
    ).toThrow(/multiple client identities/);
  });

  it('pins wire-name coverage: every server_ga4 event is consciously mapped', () => {
    for (const def of Object.values(EVENT_DEFS)) {
      if (def.allowedSinks.includes('server_ga4')) {
        expect(MP_WIRE_NAME[def.name], `wire name missing for ${def.name}`).toBeDefined();
      }
    }
    expect(MP_WIRE_NAME['payment_succeeded']).toBe('purchase');
    // purchase_viewed is browser-owned and must NOT appear here:
    expect(MP_WIRE_NAME['purchase_viewed']).toBeUndefined();
  });

  it('2xx is a receipt, never "processed"; 429/5xx retryable; other 4xx rejected', () => {
    expect(mpResponseOutcome(204)).toBe('receipt');
    expect(mpResponseOutcome(429)).toBe('retryable');
    expect(mpResponseOutcome(503)).toBe('retryable');
    expect(mpResponseOutcome(400)).toBe('rejected');
  });

  it('sendMpBatch posts the batch and reports receipt semantics', async () => {
    const calls: Array<{ url: string; body: string }> = [];
    const batch = buildMpBatch([serverEnvelope('payment_succeeded')], { resolveClientId: () => CLIENT });
    const result = await sendMpBatch(batch, {
      measurementId: 'G-TEST123',
      apiSecret: 'secret',
      fetchImpl: (async (url: string, init: RequestInit) => {
        calls.push({ url: String(url), body: String(init?.body) });
        return { status: 204 } as Response;
      }) as typeof fetch,
    });
    expect(result).toEqual({ status: 204, outcome: 'receipt', receipt: true });
    expect(calls[0]!.url).toContain('measurement_id=G-TEST123');
    expect(calls[0]!.url).toContain('api_secret=secret');
    expect(JSON.parse(calls[0]!.body).events[0].name).toBe('purchase');
  });

  it('local payload validation catches the documented bounds', () => {
    const base = { client_id: CLIENT, events: [{ name: 'purchase', params: { session_id: 's', engagement_time_msec: '1' } }] };
    expect(validateMpPayload(base)).toEqual([]);
    expect(validateMpPayload({ ...base, client_id: '' })).toContain('client_id required');
    expect(validateMpPayload({ ...base, events: Array.from({ length: MP_LIMITS.maxEventsPerRequest + 1 }, () => base.events[0]!) })).toContain(
      'events exceed 25/request',
    );
    expect(validateMpPayload({ ...base, events: [{ name: 'purchase', params: {} }] })).toEqual(
      expect.arrayContaining(['"purchase" missing session_id', '"purchase" missing engagement_time_msec']),
    );
    expect(
      validateMpPayload({ ...base, events: [{ name: 'purchase', params: { ...base.events[0]!.params, weird: { nested: true } as unknown as string } }] }),
    ).toContain('"purchase" param "weird" is not a primitive');
  });

  it('default resolver falls back to pseudonymous actor anchors', () => {
    const withAnchor = serverEnvelope('payment_succeeded', { actor: { uidHash: 'uh_1', anonId: null } });
    expect(defaultMpClientIdResolver(withAnchor)).toBe('uh_1');
    expect(defaultMpClientIdResolver(serverEnvelope('payment_succeeded'))).toBeNull();
  });
});
