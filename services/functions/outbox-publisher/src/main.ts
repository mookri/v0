// Cloud Run / worker entrypoint (deploy shell — thin by design; all
// behavior lives in publisher.ts behind ports and is contract-tested).
//
// Env: DATABASE_URL (Postgres outbox), PUBSUB_PROJECT_ID, PUBSUB_TOPIC,
// GCP_ACCESS_TOKEN_URL (or ADC token endpoint), PUBLISH_BATCH, PUBLISH_INTERVAL_MS.

import { createPostgresClient } from '@infra/database/postgres/client';
import { PostgresOutboxStore } from '@infra/messaging/outbox/PostgresOutboxStore';
import { runPublisherOnce } from './publisher';
import { PubSubTransport } from './transports/pubsub';

export interface PublisherEnv {
  DATABASE_URL?: string;
  PUBSUB_PROJECT_ID?: string;
  PUBSUB_TOPIC?: string;
  GCP_ACCESS_TOKEN?: string;
  PUBLISH_BATCH?: string;
  PUBLISH_INTERVAL_MS?: string;
}

export async function publishLoopOnce(env: PublisherEnv): Promise<void> {
  if (!env.DATABASE_URL) throw new Error('DATABASE_URL_MISSING');
  if (!env.PUBSUB_PROJECT_ID || !env.PUBSUB_TOPIC) throw new Error('PUBSUB_TARGET_MISSING');
  const store = new PostgresOutboxStore(createPostgresClient({ connectionString: env.DATABASE_URL }));
  const transport = new PubSubTransport({
    projectId: env.PUBSUB_PROJECT_ID,
    topic: env.PUBSUB_TOPIC,
    accessToken: async () => {
      if (!env.GCP_ACCESS_TOKEN) throw new Error('GCP_ACCESS_TOKEN_MISSING');
      return env.GCP_ACCESS_TOKEN;
    },
  });
  await runPublisherOnce(store, transport, {
    batch: Number(env.PUBLISH_BATCH ?? 50),
  });
}

async function main(): Promise<void> {
  const env = process.env as PublisherEnv;
  const intervalMs = Number(env.PUBLISH_INTERVAL_MS ?? 1000);
  // Persistent poll loop; each cycle is a bounded claim → publish → ack.
  for (;;) {
    try {
      await publishLoopOnce(env);
    } catch (err) {
      process.stderr.write(`outbox-publisher cycle failed: ${String(err)}\n`);
    }
    await new Promise((r) => setTimeout(r, intervalMs));
  }
}

if (process.argv[1]?.endsWith('main.ts') || process.argv[1]?.endsWith('main.js')) {
  main().catch((err) => {
    process.stderr.write(`outbox-publisher fatal: ${String(err)}\n`);
    process.exit(1);
  });
}
