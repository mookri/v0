import type { Cache, CacheOptions } from '@navdhya/contracts/src/common/cache';

export interface RedisLikeClient {
  get(key: string): Promise<string | null>;
  set(key: string, value: string, opts?: { EX?: number }): Promise<void>;
  del(key: string): Promise<void>;
}

// Adapter over an injected client — no `import redis` here; the driver is
// constructed at the composition root so business code never sees it.
export class RedisCache implements Cache {
  constructor(private readonly client: RedisLikeClient) {}

  async get<T>(key: string): Promise<T | null> {
    const raw = await this.client.get(key);
    return raw ? (JSON.parse(raw) as T) : null;
  }

  async set<T>(key: string, value: T, options?: CacheOptions): Promise<void> {
    await this.client.set(key, JSON.stringify(value), options?.ttlSeconds ? { EX: options.ttlSeconds } : undefined);
  }

  async delete(key: string): Promise<void> {
    await this.client.del(key);
  }
}
