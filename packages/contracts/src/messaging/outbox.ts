export interface OutboxRecord {
  id: string;
  aggregateId: string;
  type: string;
  payload: unknown;
  occurredAt: string;
  published: boolean;
}

export interface OutboxStore {
  append(record: Omit<OutboxRecord, 'id' | 'published'>): Promise<void>;
  claim(limit: number): Promise<OutboxRecord[]>;
  markPublished(id: string): Promise<void>;
}
