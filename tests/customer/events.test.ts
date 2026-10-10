import { test, expect, vi } from 'vitest';
import { formatSSE, parseSSEData, userChannel, SSE_HEARTBEAT } from '@navdhya/notifications/src/events/live';
import { MemoryEventBus } from '@navdhya/notifications/src/adapters/memory-bus';
import { mintActorToken, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';
import { __issueTestToken } from '@apps/customer/server/auth';
import { GET, __eventsBusForTests } from '@apps/customer/app/api/events/route';
import { createEventStream, SSE_HEARTBEAT_INTERVAL_MS } from '@apps/customer/application/events-stream';
import {
  NotificationFeed,
  partitionUnseen,
  retainNotificationFeed,
  __feedRegistryForTests,
  type NotificationItem,
} from '@apps/customer/application/events-feed';
import { buildSessionCookie } from '@apps/customer/server/backend-session';

const bearer = async (userId: string) => `Bearer ${await __issueTestToken(userId)}`;
const staleBearer = (userId: string) =>
  `Bearer ${mintActorToken(INSECURE_DEV_SECRET, { userId, sessionId: `staging_${userId}`, roles: ['customer'] }, new Date().toISOString(), 3600)}`;

const NOW = '2026-10-01T00:00:00.000Z';
const evt = (over: Record<string, unknown> = {}) => ({
  id: 'e1',
  channel: userChannel('u1'),
  type: 'booking.updated',
  at: NOW,
  data: { bookingId: 'b1' },
  ...over,
});

test('SSE wire format round-trips through the parser', () => {
  const block = formatSSE(evt());
  expect(block.startsWith('id: e1\nevent: booking.updated\n')).toBe(true);
  expect(parseSSEData(block)).toEqual({ channel: 'user:u1', at: NOW, data: { bookingId: 'b1' } });
  expect(() => parseSSEData(': ping\n\n')).toThrow('SSE_MALFORMED');
});

test('memory bus fans out and replays per channel', async () => {
  const bus = new MemoryEventBus();
  const seen: string[] = [];
  const unsub = bus.subscribe(userChannel('u1'), (e) => seen.push(e.id));
  await bus.publish(evt({ id: 'e1' }));
  await bus.publish(evt({ id: 'e2', channel: userChannel('u2') }));
  expect(seen).toEqual(['e1']);
  unsub();
  await bus.publish(evt({ id: 'e3' }));
  expect(seen).toEqual(['e1']);
  expect(bus.recent(userChannel('u1')).map((e) => e.id)).toEqual(['e1', 'e3']);
});

test('bearer tokens authenticate the events route', async () => {
  const anon = await GET(new Request('https://app.navdhya.com/api/events'));
  expect(anon.status).toBe(401);
  // Stateless token with no session: signature valid, handshake rejects.
  const ghost = await GET(
    new Request('https://app.navdhya.com/api/events?channel=user%3Au2', { headers: { authorization: staleBearer('u1') } }),
  );
  expect(ghost.status).toBe(401);
  const foreign = await GET(
    new Request('https://app.navdhya.com/api/events?channel=user%3Au2', { headers: { authorization: await bearer('u1') } }),
  );
  expect(foreign.status).toBe(403);
  const bus = __eventsBusForTests();
  await bus.publish(evt({ id: 'seed-1' }));
  const res = await GET(new Request('https://app.navdhya.com/api/events', { headers: { authorization: await bearer('u1') } }));
  expect(res.status).toBe(200);
  expect(res.headers.get('Content-Type')).toBe('text/event-stream');
  const reader = res.body!.getReader();
  const first = await reader.read();
  await reader.cancel();
  const text = new TextDecoder().decode(first.value);
  expect(text).toContain('seed-1');
});

// ---- N2: first producer for the events route (notification feed) ----

test('partitionUnseen publishes oldest-first and collapses repeats', () => {
  const seen = new Set(['n1']);
  const page: NotificationItem[] = [{ id: 'n3' }, { id: 'n2' }, { id: 'n1' }]; // newest-first
  expect(partitionUnseen(page, seen).map((item) => item.id)).toEqual(['n2', 'n3']);
  expect(partitionUnseen(page, seen)).toEqual([]); // retried page collapses
});

test('partitionUnseen caps the seen set FIFO', () => {
  const seen = new Set<string>();
  partitionUnseen([{ id: 'n4' }, { id: 'n3' }, { id: 'n2' }, { id: 'n1' }, { id: 'n0' }], seen, 3);
  expect([...seen]).toEqual(['n2', 'n3', 'n4']);
});

test('notification feed seeds once, then publishes only what arrives later', async () => {
  const bus = new MemoryEventBus();
  const channel = userChannel('u-feed');
  const published: string[] = [];
  bus.subscribe(channel, (event) => published.push(event.id));

  let page: NotificationItem[] = [{ id: 'n1', kind: 'booking', title: 'Existing' }];
  const feed = new NotificationFeed({ bus, channel, fetchLatest: async () => page });

  await feed.pollOnce(); // seed pass: record history, publish nothing
  expect(published).toEqual([]);

  page = [{ id: 'n2', kind: 'booking', title: 'Fresh' }, { id: 'n1', kind: 'booking', title: 'Existing' }];
  await feed.pollOnce();
  expect(published).toEqual(['n2']);

  await feed.pollOnce(); // no change → no duplicate
  expect(published).toEqual(['n2']);

  const live = bus.recent(channel);
  expect(live).toHaveLength(1);
  expect(live[0].type).toBe('notification.created');
  expect(live[0].channel).toBe(channel);
  expect(live[0].data).toEqual({ kind: 'booking', title: 'Fresh', body: null });
});

test('feed survives a failing backend and resumes on the next tick', async () => {
  const bus = new MemoryEventBus();
  const channel = userChannel('u-resilient');
  const published: string[] = [];
  bus.subscribe(channel, (event) => published.push(event.id));

  let calls = 0;
  const feed = new NotificationFeed({
    bus,
    channel,
    fetchLatest: async () => {
      calls++;
      if (calls === 2) throw new Error('backend down');
      return calls === 1 ? [{ id: 'n1' }] : [{ id: 'n2' }, { id: 'n1' }];
    },
  });

  await feed.pollOnce(); // seed
  await feed.pollOnce(); // failing tick — swallowed, tail survives
  await feed.pollOnce(); // recovery publishes the new notification
  expect(published).toEqual(['n2']);
});

test('the feed registry shares one instance per channel and stops on last release', async () => {
  const bus = new MemoryEventBus();
  const channel = userChannel('u-registry');
  const deps = { bus, channel, fetchLatest: async (): Promise<NotificationItem[]> => [], intervalMs: 60_000 };

  const releaseA = retainNotificationFeed(deps);
  const feed = __feedRegistryForTests().get(channel);
  expect(feed).toBeDefined();

  const releaseB = retainNotificationFeed(deps);
  expect(__feedRegistryForTests().get(channel)).toBe(feed); // shared, not replaced

  releaseA();
  expect(__feedRegistryForTests().get(channel)).toBe(feed); // one stream still open

  releaseB();
  expect(__feedRegistryForTests().has(channel)).toBe(false); // last release stops it
});

test('open streams heartbeat and close on abort', async () => {
  vi.useFakeTimers();
  try {
    const bus = new MemoryEventBus();
    const controller = new AbortController();
    const reader = createEventStream(bus, userChannel('u-hb'), controller.signal).getReader();

    await vi.advanceTimersByTimeAsync(SSE_HEARTBEAT_INTERVAL_MS);
    const beat = await reader.read();
    expect(new TextDecoder().decode(beat.value)).toBe(SSE_HEARTBEAT);

    controller.abort();
    const closed = await reader.read();
    expect(closed.done).toBe(true);

    // The interval is cleared with the stream — no stray enqueues.
    await vi.advanceTimersByTimeAsync(SSE_HEARTBEAT_INTERVAL_MS * 2);
  } finally {
    vi.useRealTimers();
  }
});

test('wired streams authenticate with the session cookie and retain the feed', async () => {
  const env = {
    CUSTOMER_BACKEND: process.env.CUSTOMER_BACKEND,
    NAVDHYA_API_BASE: process.env.NAVDHYA_API_BASE,
    SESSION_SIGNING_KEY: process.env.SESSION_SIGNING_KEY,
  };
  process.env.CUSTOMER_BACKEND = 'laravel';
  process.env.NAVDHYA_API_BASE = 'http://127.0.0.1:9';
  process.env.SESSION_SIGNING_KEY = 'feed-test-secret';
  const controller = new AbortController();
  try {
    const token = mintActorToken(
      'feed-test-secret',
      { userId: 'u-cookie', sessionId: 'sess_cookie', roles: ['customer'] },
      new Date().toISOString(),
      3600,
    );
    const res = await GET(
      new Request('https://app.navdhya.com/api/events', {
        headers: { cookie: buildSessionCookie(token, 3600) },
        signal: controller.signal,
      }),
    );
    expect(res.status).toBe(200);
    expect(res.headers.get('Content-Type')).toBe('text/event-stream');
    expect(__feedRegistryForTests().has(userChannel('u-cookie'))).toBe(true);
  } finally {
    controller.abort(); // aborting the request releases the retained feed
    for (const [key, value] of Object.entries(env)) {
      if (value === undefined) delete process.env[key];
      else process.env[key] = value;
    }
  }
  expect(__feedRegistryForTests().has(userChannel('u-cookie'))).toBe(false);
});
