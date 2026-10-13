// packages/analytics/src/client/__tests__/client.test.ts
// B1 acceptance: no-op when disabled, registry allowlist enforcement,
// P0-never-in-browser-queue, ack-only-after-positive-acknowledgement,
// batch bounds, eviction ladder, breadcrumb attachment.

import { describe, expect, it } from 'vitest';
import { createAnalyticsClient, type AnalyticsClientConfig } from '@navdhya/analytics/src/client/analytics-client';
import { MemoryOutbox } from '@navdhya/analytics/src/client/outbox';
import { UNKNOWN_CONSENT, type ConsentStatus } from '@navdhya/analytics/src/contracts/consent';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';

const GRANTED: ConsentStatus = { analytics: 'GRANTED', ads: 'DENIED' };

interface Harness {
  client: ReturnType<typeof createAnalyticsClient>;
  outbox: MemoryOutbox;
  sent: SignalEnvelope[][];
  setFail: (v: boolean) => void;
}

function setup(overrides: Partial<AnalyticsClientConfig> = {}): Harness {
  const outbox = new MemoryOutbox();
  const sent: SignalEnvelope[][] = [];
  const state = { fail: false };
  const client = createAnalyticsClient({
    enabled: true,
    surface: 'web',
    appVersion: '1.0.0-test',
    outbox,
    consent: () => GRANTED,
    routeTemplate: () => '/pujas?utm_source=x',
    onWarn: () => {},
    send: async (batch) => {
      if (state.fail) throw new Error('collector down');
      sent.push(batch);
    },
    ...overrides,
  });
  return { client, outbox, sent, setFail: (v) => (state.fail = v) };
}

describe('no-op safety', () => {
  it('disabled client never accepts or sends (default: off, no keys)', async () => {
    const { client, outbox } = setup({ enabled: false, consent: () => GRANTED });
    expect(client.enabled).toBe(false);
    expect(client.track('page_view', { page_type: 'home' })).toBe(false);
    expect(await client.flush()).toEqual({ sent: 0, failed: 0 });
    expect(await outbox.size()).toBe(0);
    client.recordBreadcrumb('nav', 'should also no-op');
    expect(client.snapshotBreadcrumbs()).toEqual([]);
  });

  it('fail-closed: UNKNOWN consent blocks all collection', async () => {
    const { client, outbox } = setup({ consent: () => UNKNOWN_CONSENT });
    expect(client.track('page_view', { page_type: 'home' })).toBe(false);
    expect(await outbox.size()).toBe(0);
  });
});

describe('registry allowlist enforcement', () => {
  it('rejects unknown events', () => {
    const { client } = setup();
    expect(client.track('totally_made_up_event')).toBe(false);
  });

  it('rejects server-produced (P0) events — never enters the browser queue', async () => {
    const { client, outbox } = setup();
    expect(client.track('booking_confirmed', { value: 5100, currency: 'INR', service_category: 'puja' })).toBe(false);
    expect(client.track('payment_succeeded', { value: 5100, currency: 'INR', transaction_ref: 'order_x' })).toBe(false);
    expect(await outbox.size()).toBe(0);
  });

  it('rejects events not permitted on the current surface', () => {
    const { client } = setup();
    expect(client.track('kyc_submitted', { doc_type: 'id_proof' })).toBe(false); // partner-only on web
  });

  it('rejects invalid, missing and unknown params (fail-closed allowlist)', () => {
    const { client } = setup();
    expect(client.track('page_view')).toBe(false); // missing required
    expect(client.track('page_view', { page_type: 42 as unknown as string })).toBe(false); // wrong type
    expect(client.track('page_view', { page_type: 'home', smuggled: 'param' })).toBe(false); // unknown param
  });
});

describe('envelope construction', () => {
  it('sequences session.seq from 1, assigns unique ids, strips query strings', async () => {
    const { client, outbox } = setup();
    expect(client.track('page_view', { page_type: 'catalog' })).toBe(true);
    expect(client.track('page_view', { page_type: 'home' })).toBe(true);
    const entries = outbox.snapshot();
    expect(entries.map((e) => e.envelope.session.seq)).toEqual([1, 2]);
    expect(entries[0]!.envelope.id).not.toBe(entries[1]!.envelope.id);
    expect(entries[0]!.envelope.context.routeTemplate).toBe('/pujas');
    expect(entries[0]!.envelope.consent).toEqual({ analytics: true, ads: false });
  });

  it('attaches drained breadcrumbs only to app_error (allowsBreadcrumbs)', async () => {
    const { client, outbox } = setup();
    client.recordBreadcrumb('nav', 'route change to checkout', '/booking');
    client.recordBreadcrumb('ui', 'payment form submitted', '/booking');
    expect(client.snapshotBreadcrumbs()).toHaveLength(2);

    expect(client.track('app_error', { error_class: 'TypeError', release: 'r1' })).toBe(true);
    expect(outbox.snapshot()[0]!.envelope.breadcrumbs).toHaveLength(2);
    expect(client.snapshotBreadcrumbs()).toHaveLength(0); // drained

    expect(client.track('page_view', { page_type: 'checkout' })).toBe(true);
    expect(outbox.snapshot()[1]!.envelope.breadcrumbs).toBeUndefined();
  });
});

describe('delivery: peek → send → ack', () => {
  it('positive acknowledgement removes events; failures keep them queued', async () => {
    const { client, outbox, sent, setFail } = setup();

    setFail(true);
    expect(client.track('page_view', { page_type: 'home' })).toBe(true);
    expect(await client.flush()).toEqual({ sent: 0, failed: 1 });
    expect(await outbox.size()).toBe(1); // NOT removed — no positive ack

    setFail(false);
    expect(await client.flush()).toEqual({ sent: 1, failed: 0 });
    expect(await outbox.size()).toBe(0);
    expect(sent).toHaveLength(1);
  });

  it('batches never exceed maxBatch (GA4/collector request limit: 25)', async () => {
    const { client, sent } = setup();
    for (let i = 0; i < 30; i++) client.track('page_view', { page_type: 'catalog' });
    expect(await client.flush()).toEqual({ sent: 25, failed: 0 });
    expect(await client.flush()).toEqual({ sent: 5, failed: 0 });
    expect(sent.map((b) => b.length)).toEqual([25, 5]);
  });

  it('default send without collectorUrl fails honestly (counted, stays queued)', async () => {
    const outbox = new MemoryOutbox();
    const client = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0-test',
      outbox,
      consent: () => GRANTED,
      onWarn: () => {},
    });
    client.track('page_view', { page_type: 'home' });
    expect(await client.flush()).toEqual({ sent: 0, failed: 1 });
    expect(await outbox.size()).toBe(1);
  });
});

describe('queue bounds and eviction ladder', () => {
  it('sheds P2 before P1 when over maxQueue; P0 never present to shed', async () => {
    const { client, outbox } = setup({ maxQueue: 6 });
    for (let i = 0; i < 3; i++) client.track('page_view', { page_type: 'catalog' }); // P1
    for (let i = 0; i < 4; i++) client.track('app_error', { error_class: 'X', release: 'r1' }); // P2
    await new Promise((r) => setTimeout(r, 0)); // let enqueue→shed chain settle

    const entries = outbox.snapshot();
    expect(entries).toHaveLength(6);
    const priorities = new Set(entries.map((e) => e.priority));
    expect(priorities.has('P0')).toBe(false);
    // 4 P2 tracked, 1 shed → 3 remain alongside 3 P1
    expect(entries.filter((e) => e.priority === 'P2')).toHaveLength(3);
    expect(entries.filter((e) => e.priority === 'P1')).toHaveLength(3);
  });
});
