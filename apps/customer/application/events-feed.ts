import type { LiveEvent } from '@navdhya/notifications/src/events/live';
import type { EventBus } from '@navdhya/notifications/src/ports/event-bus';

// Serialized customer notification (parity-customers.md: the notifications
// read shape — id/object/kind/category/title/body/read_at).
export interface NotificationItem {
  id: string;
  object?: string;
  kind?: string;
  category?: string;
  title?: string;
  body?: string | null;
  read_at?: string | null;
}

export interface NotificationFeedDeps {
  bus: EventBus;
  channel: string;
  /** Newest-first page of the caller's notifications (backend read model). */
  fetchLatest: () => Promise<NotificationItem[]>;
  intervalMs?: number;
  seenCap?: number;
}

/** Live tail cadence — new notifications surface within one tick. */
export const NOTIFICATION_FEED_INTERVAL_MS = 5_000;

// Map a notification row to its live event. The notification id IS the
// event id: SSE `id:` lines, Last-Event-ID replay and the bus buffer all
// key off the same identity (at-least-once without visible duplicates).
export function notificationToLiveEvent(item: NotificationItem, channel: string, at: string): LiveEvent {
  return {
    id: String(item.id),
    channel,
    type: 'notification.created',
    at,
    data: { kind: item.kind ?? 'system', title: item.title ?? '', body: item.body ?? null },
  };
}

// Newest-first page → not-yet-seen items in publish order (oldest first,
// so a burst arrives chronologically). Seen ids are recorded as a side
// effect and capped FIFO: duplicates from a retried page or an
// overlapping tick collapse instead of re-emitting.
export function partitionUnseen(items: NotificationItem[], seen: Set<string>, seenCap = 1_000): NotificationItem[] {
  const fresh: NotificationItem[] = [];
  for (let i = items.length - 1; i >= 0; i--) {
    const item = items[i];
    const id = String(item.id);
    if (seen.has(id)) continue;
    fresh.push(item);
    seen.add(id);
  }
  while (seen.size > seenCap) {
    const oldest = seen.values().next();
    if (oldest.done) break;
    seen.delete(oldest.value);
  }
  return fresh;
}

// The first fetch only records what already exists — history belongs to
// the REST list, the stream is a live tail from connect onward.
export class NotificationFeed {
  private timer: ReturnType<typeof setInterval> | null = null;
  private refCount = 0;
  private readonly seen: Set<string>;
  private seeded = false;
  private ticking = false;

  constructor(private readonly deps: NotificationFeedDeps) {
    this.seen = new Set<string>();
  }

  get isEmpty(): boolean {
    return this.refCount === 0;
  }

  /** First retain starts the tail; last release stops it (one tail per channel). */
  retain(): () => void {
    this.refCount++;
    if (this.refCount === 1) this.start();
    let released = false;
    return () => {
      if (released) return;
      released = true;
      this.refCount--;
      if (this.refCount === 0) this.stop();
    };
  }

  /** One poll against the backend. Public so tests drive ticks directly. */
  async pollOnce(): Promise<void> {
    if (this.ticking) return; // never overlap polls
    this.ticking = true;
    try {
      const items = (await this.deps.fetchLatest()) ?? [];
      const fresh = partitionUnseen(items, this.seen, this.deps.seenCap);
      if (!this.seeded) {
        this.seeded = true;
        return; // seed pass: record, don't replay history
      }
      for (const item of fresh) {
        await this.deps.bus.publish(notificationToLiveEvent(item, this.deps.channel, new Date().toISOString()));
      }
    } catch {
      // Backend hiccup: keep the tail alive — the next tick retries and
      // the seen set still collapses anything that landed meanwhile.
    } finally {
      this.ticking = false;
    }
  }

  private start(): void {
    void this.pollOnce(); // seed immediately so the first live tick is close
    this.timer = setInterval(() => void this.pollOnce(), this.deps.intervalMs ?? NOTIFICATION_FEED_INTERVAL_MS);
    (this.timer as unknown as { unref?: () => void }).unref?.();
  }

  private stop(): void {
    if (this.timer !== null) clearInterval(this.timer);
    this.timer = null;
    this.seeded = false;
    this.seen.clear();
  }
}

// One feed per channel per process: the first stream retains it (refcount
// 1), every additional stream shares it, the last release stops it — so
// N open tabs mean one backend poll, not N.
const registry = new Map<string, NotificationFeed>();

export function retainNotificationFeed(deps: NotificationFeedDeps): () => void {
  let feed = registry.get(deps.channel);
  if (!feed) {
    feed = new NotificationFeed(deps);
    registry.set(deps.channel, feed);
  }
  const active = feed;
  const release = active.retain();
  return () => {
    release();
    if (active.isEmpty) registry.delete(deps.channel);
  };
}

export function __feedRegistryForTests(): Map<string, NotificationFeed> {
  return registry;
}
