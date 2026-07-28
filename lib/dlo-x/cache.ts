// lib/dlo-x/cache.ts

/**
 * Bounded in-memory cache with TTL and LRU eviction.
 *
 * P0 audit fix: the previous implementation was an unbounded
 * `Map<string, { value, expiry }>`. At our 5K RPS target, even with
 * 1KB entries per value, that grows at ~5MB/sec = 18GB/hour, all in
 * process memory. A long-running instance would OOM within hours
 * (and instantly if an attacker deliberately sent many unique cache
 * keys — e.g. random `?bust=…` query params).
 *
 * This implementation:
 *   - Hard caps the entry count (`maxEntries`); when full, the
 *     least-recently-used entry is evicted on every `set`.
 *   - Honors TTL (per-entry expiry checked on read and on insertion).
 *   - Preserves the `Map`-based LRU order (Map iteration order is
 *     insertion order; `set` of an existing key preserves its
 *     position, and deleting-then-inserting moves it to the tail).
 *   - `cleanup()` is still available for explicit sweeps.
 *
 * In PR 11.5 this will be replaced with a Redis-backed implementation
 * (Aurora Global → ElastiCache Redis cluster) for cross-instance
 * sharing. The `CacheInterface` keeps the migration surface small.
 */

interface CacheEntry<V> {
  value: V;
  expiry: number;
}

export class InMemoryCache<V = unknown> {
  private readonly cache = new Map<string, CacheEntry<V>>();
  private readonly maxEntries: number;

  constructor(options: { maxEntries?: number } = {}) {
    this.maxEntries = options.maxEntries ?? 10_000;
  }

  set(key: string, value: V, ttl?: number): void {
    const expiry = ttl !== undefined ? Date.now() + ttl : Infinity;

    // If the key is already present, delete it first so re-inserting
    // moves it to the tail (most-recently-used) of the LRU order.
    if (this.cache.has(key)) {
      this.cache.delete(key);
    }
    this.cache.set(key, { value, expiry });

    // Evict from the head (least-recently-used) until under the cap.
    while (this.cache.size > this.maxEntries) {
      const oldest = this.cache.keys().next();
      if (oldest.done) break;
      this.cache.delete(oldest.value);
    }
  }

  get(key: string): V | undefined {
    const item = this.cache.get(key);
    if (!item) return undefined;
    if (Date.now() > item.expiry) {
      this.cache.delete(key);
      return undefined;
    }
    // Promote to MRU by delete-then-set.
    this.cache.delete(key);
    this.cache.set(key, item);
    return item.value;
  }

  delete(key: string): void {
    this.cache.delete(key);
  }

  cleanup(): number {
    const now = Date.now();
    let removed = 0;
    for (const [key, item] of this.cache) {
      if (now > item.expiry) {
        this.cache.delete(key);
        removed++;
      }
    }
    return removed;
  }

  clear(): void {
    this.cache.clear();
  }

  size(): number {
    return this.cache.size;
  }

  /** Test / diagnostics. */
  capacity(): number {
    return this.maxEntries;
  }
}

// Singleton layout cache. Sized for ~5K RPS × 2-3 layouts per
// request = ~10K-15K distinct keys before eviction. Tune in
// PR 11.5 when we move to Redis.
export const layoutCache = new InMemoryCache<unknown>({ maxEntries: 10_000 });

// Redis-like cache interface for future Redis implementation
export interface CacheInterface {
  set(key: string, value: unknown, ttl?: number): void;
  get(key: string): unknown | undefined;
  delete(key: string): void;
  clear(): void;
}

// For production, you might want to use Redis
// export class RedisCache implements CacheInterface {
//   private redisClient: any; // Redis client
//
//   constructor(redisClient: any) {
//     this.redisClient = redisClient;
//   }
//
//   async set(key: string, value: any, ttl?: number) {
//     if (ttl) {
//       await this.redisClient.setex(key, Math.floor(ttl / 1000), JSON.stringify(value));
//     } else {
//       await this.redisClient.set(key, JSON.stringify(value));
//     }
//   }
//
//   async get(key: string): Promise<any | undefined> {
//     const value = await this.redisClient.get(key);
//     return value ? JSON.parse(value) : undefined;
//   }
//
//   async delete(key: string) {
//     await this.redisClient.del(key);
//   }
//
//   async clear() {
//     await this.redisClient.flushall();
//   }
// }