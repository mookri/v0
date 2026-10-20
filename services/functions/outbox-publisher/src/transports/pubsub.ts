// Pub/Sub publish adapter (REST, `topics.publish`). Auth is an injected
// access-token provider — same pattern as Razorpay/BigQuery adapters:
// credentials stay server-side, fetchFn is injectable for tests.
//
// Pub/Sub has no native publish-side dedup; the stable event ID travels in
// `attributes` and dedup is enforced at the sink (envelope-ID insertId).
// Re-publishes after a crash therefore surface as duplicate messages and are
// absorbed downstream — never as duplicate rows.

import type { EventTransport, PublishedMessage } from '../publisher';

export interface PubSubTransportConfig {
  projectId: string;
  topic: string;
  accessToken: () => Promise<string>;
  fetchFn?: typeof fetch;
}

export class PubSubTransport implements EventTransport {
  private readonly fetchFn: typeof fetch;

  constructor(private readonly config: PubSubTransportConfig) {
    if (!config.projectId || !config.topic) throw new Error('PUBSUB_TARGET_MISSING');
    this.fetchFn = config.fetchFn ?? fetch;
  }

  async publish(message: PublishedMessage): Promise<void> {
    const token = await this.config.accessToken();
    const url = `https://pubsub.googleapis.com/v1/projects/${this.config.projectId}/topics/${this.config.topic}:publish`;
    const res = await this.fetchFn(url, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      body: JSON.stringify({
        messages: [
          {
            data: Buffer.from(JSON.stringify(message.payload), 'utf8').toString('base64'),
            attributes: { 'event-id': message.id, 'event-type': message.type },
          },
        ],
      }),
    });
    if (!res.ok) throw new Error(`PUBSUB_PUBLISH_FAILED:${res.status}`);
    const body = (await res.json()) as { messageIds?: string[] };
    if (!Array.isArray(body.messageIds) || body.messageIds.length !== 1) {
      throw new Error('PUBSUB_PUBLISH_NO_MESSAGE_ID');
    }
  }
}
