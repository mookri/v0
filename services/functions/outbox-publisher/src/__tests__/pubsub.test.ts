// Pub/Sub transport contract: real endpoint shape, auth, attribute dedup key.

import { describe, expect, it, vi } from 'vitest';
import { PubSubTransport } from '../transports/pubsub';

function fakeFetch(response: { ok: boolean; status?: number; json?: unknown }) {
  const calls: Array<{ url: string; init: RequestInit }> = [];
  const fn = vi.fn(async (url: string, init: RequestInit) => {
    calls.push({ url, init });
    return {
      ok: response.ok,
      status: response.status ?? 200,
      json: async () => response.json ?? {},
    } as Response;
  }) as unknown as typeof fetch;
  return { calls, fn };
}

const message = { id: '01JULID000000000000000001', type: 'payment_succeeded', payload: { value: 5100 } };

describe('PubSubTransport', () => {
  it('publishes to the topic endpoint with bearer auth, event-id attribute and base64 data', async () => {
    const { calls, fn } = fakeFetch({ ok: true, json: { messageIds: ['m1'] } });
    const transport = new PubSubTransport({
      projectId: 'proj',
      topic: 'signals-domain-events',
      accessToken: async () => 'tok_123',
      fetchFn: fn,
    });

    await transport.publish(message);

    expect(calls).toHaveLength(1);
    expect(calls[0]!.url).toBe('https://pubsub.googleapis.com/v1/projects/proj/topics/signals-domain-events:publish');
    const headers = calls[0]!.init.headers as Record<string, string>;
    expect(headers.Authorization).toBe('Bearer tok_123');
    const body = JSON.parse(String(calls[0]!.init.body)) as {
      messages: Array<{ data: string; attributes: Record<string, string> }>;
    };
    expect(body.messages[0]!.attributes['event-id']).toBe(message.id);
    expect(body.messages[0]!.attributes['event-type']).toBe(message.type);
    expect(JSON.parse(Buffer.from(body.messages[0]!.data, 'base64').toString('utf8'))).toEqual(message.payload);
  });

  it('throws on non-2xx and on a response without a message ID (never fake-acks)', async () => {
    const down = fakeFetch({ ok: false, status: 503 });
    const t1 = new PubSubTransport({ projectId: 'p', topic: 't', accessToken: async () => 'x', fetchFn: down.fn });
    await expect(t1.publish(message)).rejects.toThrow('PUBSUB_PUBLISH_FAILED:503');

    const noId = fakeFetch({ ok: true, json: { messageIds: [] } });
    const t2 = new PubSubTransport({ projectId: 'p', topic: 't', accessToken: async () => 'x', fetchFn: noId.fn });
    await expect(t2.publish(message)).rejects.toThrow('PUBSUB_PUBLISH_NO_MESSAGE_ID');
  });

  it('requires target config and a token', async () => {
    expect(() => new PubSubTransport({ projectId: '', topic: 't', accessToken: async () => 'x' })).toThrow(
      'PUBSUB_TARGET_MISSING',
    );
    const { fn } = fakeFetch({ ok: true, json: { messageIds: ['m'] } });
    const t = new PubSubTransport({
      projectId: 'p',
      topic: 't',
      accessToken: async () => {
        throw new Error('GCP_ACCESS_TOKEN_MISSING');
      },
      fetchFn: fn,
    });
    await expect(t.publish(message)).rejects.toThrow('GCP_ACCESS_TOKEN_MISSING');
  });
});
