import { test, expect } from 'vitest';
import { formatSSE, parseSSEData, userChannel } from '@navdhya/notifications/src/events/live';
import { MemoryEventBus } from '@navdhya/notifications/src/adapters/memory-bus';
import { mintActorToken, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';
import { GET, __eventsBusForTests } from '@apps/customer/app/api/events/route';

const bearer = (userId: string) =>
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
  const foreign = await GET(
    new Request('https://app.navdhya.com/api/events?channel=user%3Au2', { headers: { authorization: bearer('u1') } }),
  );
  expect(foreign.status).toBe(403);
  const bus = __eventsBusForTests();
  await bus.publish(evt({ id: 'seed-1' }));
  const res = await GET(new Request('https://app.navdhya.com/api/events', { headers: { authorization: bearer('u1') } }));
  expect(res.status).toBe(200);
  expect(res.headers.get('Content-Type')).toBe('text/event-stream');
  const reader = res.body!.getReader();
  const first = await reader.read();
  await reader.cancel();
  const text = new TextDecoder().decode(first.value);
  expect(text).toContain('seed-1');
});
