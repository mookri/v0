export interface CacheOptions {
  ttlSeconds?: number;
}

export interface Cache {
  get<T>(key: string): Promise<T | null>;
  set<T>(key: string, value: T, options?: CacheOptions): Promise<void>;
  delete(key: string): Promise<void>;
}

export function cacheKey(namespace: string, version: string, id: string): string {
  return `navdhya:${namespace}:${version}:${id}`;
}
