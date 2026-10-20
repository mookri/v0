// packages/analytics/src/client/outbox.ts
//
// Persist-before-delivery queue. Contract (architecture §4.2):
//   - persist eligible events BEFORE attempting delivery,
//   - REMOVE only after a positive collector acknowledgement (ack()),
//   - bounded queue with an explicit eviction ladder: shed P3 first, then
//     P2, then P1. P0 never enters this queue — the SDK rejects client-side
//     emission of server-owned events entirely.
//
// IndexedDbOutbox is the browser implementation; MemoryOutbox is the test /
// no-IDB fallback (same interface, so tests exercise the real contract).

import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import type { EventPriority } from '@navdhya/analytics/src/registry/types';

export interface OutboxEntry {
  envelope: SignalEnvelope;
  priority: EventPriority;
  attempts: number;
  enqueuedAt: number;
}

export interface Outbox {
  enqueue(entry: OutboxEntry): Promise<void>;
  /** First n entries in enqueue order, WITHOUT removing them. */
  peek(max: number): Promise<OutboxEntry[]>;
  /** Remove entries the collector positively acknowledged. */
  ack(ids: string[]): Promise<void>;
  size(): Promise<number>;
  /**
   * Enforce a queue bound by dropping the lowest-priority class present
   * (P3 → P2 → P1), oldest first within a class. Returns entries removed.
   */
  shedTo(maxSize: number): Promise<number>;
}

const SHED_ORDER: EventPriority[] = ['P3', 'P2', 'P1', 'P0'];

export class MemoryOutbox implements Outbox {
  private items: OutboxEntry[] = [];

  async enqueue(entry: OutboxEntry): Promise<void> {
    this.items.push(entry);
  }

  async peek(max: number): Promise<OutboxEntry[]> {
    return this.items.slice(0, max);
  }

  async ack(ids: string[]): Promise<void> {
    const set = new Set(ids);
    this.items = this.items.filter((e) => !set.has(e.envelope.id));
  }

  async size(): Promise<number> {
    return this.items.length;
  }

  async shedTo(maxSize: number): Promise<number> {
    let removed = 0;
    while (this.items.length > maxSize) {
      let victimIdx = -1;
      for (const prio of SHED_ORDER) {
        victimIdx = this.items.findIndex((e) => e.priority === prio);
        if (victimIdx >= 0) break;
      }
      if (victimIdx < 0) break;
      this.items.splice(victimIdx, 1);
      removed++;
    }
    return removed;
  }

  /** Test helper: current entries, oldest first. */
  snapshot(): OutboxEntry[] {
    return [...this.items];
  }
}

const DB_NAME = 'navdhya-signals';
const STORE = 'events';
const DB_VERSION = 1;

function openDb(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, DB_VERSION);
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(STORE)) {
        const store = db.createObjectStore(STORE, { keyPath: 'k', autoIncrement: true });
        store.createIndex('eventId', 'eventId', { unique: true });
      }
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error ?? new Error('indexedDB open failed'));
  });
}

function tx<T>(db: IDBDatabase, mode: IDBTransactionMode, fn: (store: IDBObjectStore) => IDBRequest<T>): Promise<T> {
  return new Promise((resolve, reject) => {
    const t = db.transaction(STORE, mode);
    const req = fn(t.objectStore(STORE));
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error ?? new Error('indexedDB request failed'));
  });
}

interface StoredEntry extends OutboxEntry {
  k?: number;
  eventId: string;
}

/** Browser-persistent outbox. Requires a DOM-like indexedDB global. */
export class IndexedDbOutbox implements Outbox {
  private dbPromise: Promise<IDBDatabase> | null = null;

  private db(): Promise<IDBDatabase> {
    if (!this.dbPromise) this.dbPromise = openDb();
    return this.dbPromise;
  }

  async enqueue(entry: OutboxEntry): Promise<void> {
    const db = await this.db();
    const stored: StoredEntry = { ...entry, eventId: entry.envelope.id };
    await tx(db, 'readwrite', (s) => s.add(stored) as IDBRequest<unknown>);
  }

  async peek(max: number): Promise<OutboxEntry[]> {
    const db = await this.db();
    const all = (await tx(db, 'readonly', (s) => s.getAll() as IDBRequest<StoredEntry[]>)) ?? [];
    return all
      .slice()
      .sort((a, b) => (a.k ?? 0) - (b.k ?? 0))
      .slice(0, max)
      .map(({ eventId: _eventId, k: _k, ...entry }) => entry);
  }

  async ack(ids: string[]): Promise<void> {
    if (ids.length === 0) return;
    const db = await this.db();
    const index = db.transaction(STORE, 'readonly').objectStore(STORE).index('eventId');
    for (const id of ids) {
      const key = await new Promise<IDBValidKey | undefined>((resolve, reject) => {
        const req = index.getKey(id);
        req.onsuccess = () => resolve(req.result as IDBValidKey | undefined);
        req.onerror = () => reject(req.error);
      });
      if (key !== undefined) {
        await tx(db, 'readwrite', (s) => s.delete(key) as IDBRequest<undefined>);
      }
    }
  }

  async size(): Promise<number> {
    const db = await this.db();
    return (await tx(db, 'readonly', (s) => s.count() as IDBRequest<number>)) ?? 0;
  }

  async shedTo(maxSize: number): Promise<number> {
    let removed = 0;
    // Load once, shed from the lowest priority class until within budget.
    for (;;) {
      const all = await this.peekAll();
      if (all.length <= maxSize) break;
      let victim: StoredEntry | undefined;
      for (const prio of SHED_ORDER) {
        victim = all.find((e) => e.priority === prio);
        if (victim) break;
      }
      if (!victim || victim.k === undefined) break;
      const db = await this.db();
      await tx(db, 'readwrite', (s) => s.delete(victim!.k!) as IDBRequest<undefined>);
      removed++;
      if (removed > 10_000) break; // safety valve; never loop unbounded
    }
    return removed;
  }

  private async peekAll(): Promise<StoredEntry[]> {
    const db = await this.db();
    const all = (await tx(db, 'readonly', (s) => s.getAll() as IDBRequest<StoredEntry[]>)) ?? [];
    return all.slice().sort((a, b) => (a.k ?? 0) - (b.k ?? 0));
  }
}

/** IDB when available, memory otherwise (SSR, tests, embedded webviews). */
export function createDefaultOutbox(): Outbox {
  if (typeof indexedDB !== 'undefined') return new IndexedDbOutbox();
  return new MemoryOutbox();
}
