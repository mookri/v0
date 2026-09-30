import type { OutboxStore } from '../../packages/contracts/src/messaging/outbox';

export interface OutboxDispatcher {
  dispatch(record: { id: string; type: string; payload: unknown }): Promise<void>;
}

// Single worker runtime today; split per-domain handlers later.
export async function runOutboxOnce(store: OutboxStore, dispatcher: OutboxDispatcher): Promise<number> {
  const batch = await store.claim(50);
  for (const record of batch) {
    await dispatcher.dispatch(record);
    await store.markPublished(record.id);
  }
  return batch.length;
}
