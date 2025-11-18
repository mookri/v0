// lib/dlo-x/cache.ts

// Simple in-memory cache with TTL support
class InMemoryCache {
  private cache: Map<string, { value: any; expiry: number }> = new Map();
  
  // Set a value with optional TTL in milliseconds
  set(key: string, value: any, ttl?: number) {
    const expiry = ttl ? Date.now() + ttl : Infinity;
    this.cache.set(key, { value, expiry });
  }
  
  // Get a value if it exists and hasn't expired
  get(key: string): any | undefined {
    const item = this.cache.get(key);
    
    if (!item) {
      return undefined;
    }
    
    if (Date.now() > item.expiry) {
      this.cache.delete(key);
      return undefined;
    }
    
    return item.value;
  }
  
  // Delete a key
  delete(key: string) {
    this.cache.delete(key);
  }
  
  // Clear all expired entries
  cleanup() {
    const now = Date.now();
    for (const [key, item] of this.cache.entries()) {
      if (now > item.expiry) {
        this.cache.delete(key);
      }
    }
  }
  
  // Clear all entries
  clear() {
    this.cache.clear();
  }
  
  // Get cache size
  size(): number {
    return this.cache.size;
  }
}

// Create a singleton instance
export const layoutCache = new InMemoryCache();

// Redis-like cache interface for future Redis implementation
export interface CacheInterface {
  set(key: string, value: any, ttl?: number): void;
  get(key: string): any | undefined;
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
//   async get(key: string) {
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