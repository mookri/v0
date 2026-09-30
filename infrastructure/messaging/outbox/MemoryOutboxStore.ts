import type { OutboxRecord, OutboxStore } from '@navdhya/contracts/src/messaging/outbox';

export class MemoryOutboxStore implements OutboxStore {
  private readonly records = new Map<string, OutboxRecord>();
  private seq = 0;

  async append(record: Omit<OutboxRecord, 'id' | 'published'>): Promise<void> {
    const id = `outbox_${++this.seq}`;
    this.records.set(id, { ...record, id, published: false });
  }

  async claim(limit: number): Promise<OutboxRecord[]> {
    return [...this.records.values()].filter((r) => !r.published).slice(0, limit);
  }

  async markPublished(id: string): Promise<void> {
    const rec = this.records.get(id);
    if (rec) this.records.set(id, { ...rec, published: true });
  }
}
