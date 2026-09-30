import { test, expect } from 'vitest';
import { formatSSE, parseSSEData, userChannel } from '@navdhya/notifications/src/events/live';
import { MemoryEventBus } from '@navdhya/notifications/src/adapters/memory-bus';
import { StagingHeaderActorResolver } from '@apps/customer/application/actor';
import { GET, __eventsBusForTests } from '@apps/customer/app/api/events/route';

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

test('staging actor resolver implements the identity port shape', async () => {
  const r = new StagingHeaderActorResolver();
  expect(await r.resolve({})).toBeNull();
  expect(await r.resolve({ 'x-user-id': 'u1' })).toEqual({ userId: 'u1', sessionId: 'staging_u1', roles: ['customer'] });
});

test('events route gates auth and channel ownership', async () => {
  const anon = await GET(new Request('https://app.navdhya.com/api/events'));
  expect(anon.status).toBe(401);
  const foreign = await GET(
    new Request('https://app.navdhya.com/api/events?channel=user%3Au2', { headers: { 'x-user-id': 'u1' } }),
  );
  expect(foreign.status).toBe(403);
  const bus = __eventsBusForTests();
  await bus.publish(evt({ id: 'seed-1' }));
  const res = await GET(new Request('https://app.navdhya.com/api/events', { headers: { 'x-user-id': 'u1' } }));
  expect(res.status).toBe(200);
  expect(res.headers.get('Content-Type')).toBe('text/event-stream');
  const reader = res.body!.getReader();
  const first = await reader.read();
  await reader.cancel();
  const text = new TextDecoder().decode(first.value);
  expect(text).toContain('seed-1');
});
