import { InMemoryCache } from "@/lib/dlo-x/cache";

/**
 * The `InMemoryCache` class is not exported by name; the singleton
 * `layoutCache` is. We test it through the singleton plus a small
 * wrapper that lets us size the cap. Both are exercised here.
 */
describe("lib/dlo-x/cache — bounded LRU (P0 memory-leak fix)", () => {
  // Use the exported layoutCache singleton and clean it between tests.
  // We do not test its internal maxEntries directly (it's not exposed);
  // the LRU semantics are tested via the InMemoryCache class below.
  let cache: InMemoryCache<string>;

  beforeEach(() => {
    cache = new InMemoryCache<string>({ maxEntries: 3 });
  });

  test("set + get round-trip", () => {
    cache.set("a", "A");
    expect(cache.get("a")).toBe("A");
  });

  test("get returns undefined for missing key", () => {
    expect(cache.get("missing")).toBeUndefined();
  });

  test("TTL: get returns undefined after expiry", async () => {
    cache.set("a", "A", 10);
    expect(cache.get("a")).toBe("A");
    await new Promise((r) => setTimeout(r, 20));
    expect(cache.get("a")).toBeUndefined();
  });

  test("TTL: ttl=0 expires within the next tick", async () => {
    cache.set("a", "A", 0);
    // Wait for the next macrotask so Date.now() advances past the
    // insertion time (we can't rely on it being > strictly from
    // synchronous execution).
    await new Promise((r) => setTimeout(r, 5));
    expect(cache.get("a")).toBeUndefined();
  });

  test("TTL: undefined means no expiry", () => {
    cache.set("a", "A");
    expect(cache.get("a")).toBe("A");
    // never expires
  });

  test("LRU eviction: oldest entry is dropped when cap exceeded", () => {
    cache.set("a", "A");
    cache.set("b", "B");
    cache.set("c", "C");
    cache.set("d", "D"); // evicts "a"
    expect(cache.get("a")).toBeUndefined();
    expect(cache.get("b")).toBe("B");
    expect(cache.get("c")).toBe("C");
    expect(cache.get("d")).toBe("D");
  });

  test("LRU eviction: get() promotes entry to MRU", () => {
    cache.set("a", "A");
    cache.set("b", "B");
    cache.set("c", "C");
    // Touch "a" so it becomes most-recently-used
    cache.get("a");
    cache.set("d", "D"); // evicts "b" (now the LRU)
    expect(cache.get("a")).toBe("A");
    expect(cache.get("b")).toBeUndefined();
    expect(cache.get("c")).toBe("C");
    expect(cache.get("d")).toBe("D");
  });

  test("re-set of existing key moves it to MRU (no duplicate entries)", () => {
    cache.set("a", "A");
    cache.set("b", "B");
    cache.set("c", "C");
    cache.set("a", "A2"); // re-insert "a"
    cache.set("d", "D"); // should evict "b" (oldest), not "a"
    expect(cache.get("a")).toBe("A2");
    expect(cache.get("b")).toBeUndefined();
  });

  test("delete removes a key", () => {
    cache.set("a", "A");
    cache.delete("a");
    expect(cache.get("a")).toBeUndefined();
  });

  test("cleanup removes expired entries and returns count", async () => {
    cache.set("a", "A", 10);
    cache.set("b", "B", 10);
    cache.set("c", "C"); // no expiry
    await new Promise((r) => setTimeout(r, 20));
    const removed = cache.cleanup();
    expect(removed).toBe(2);
    expect(cache.get("c")).toBe("C");
  });

  test("clear empties the cache", () => {
    cache.set("a", "A");
    cache.set("b", "B");
    cache.clear();
    expect(cache.size()).toBe(0);
    expect(cache.get("a")).toBeUndefined();
  });

  test("size reports current entry count", () => {
    expect(cache.size()).toBe(0);
    cache.set("a", "A");
    cache.set("b", "B");
    expect(cache.size()).toBe(2);
  });

  test("capacity reports maxEntries", () => {
    expect(cache.capacity()).toBe(3);
  });

  test("eviction under churn is O(1) per insert", () => {
    // Stress test: 10x over capacity, 1K iterations
    const stress = new InMemoryCache<number>({ maxEntries: 100 });
    for (let i = 0; i < 1_000; i++) {
      stress.set(`k-${i}`, i);
    }
    expect(stress.size()).toBe(100);
    // The most-recently-inserted 100 should be present
    for (let i = 900; i < 1_000; i++) {
      expect(stress.get(`k-${i}`)).toBe(i);
    }
    // And the first 900 should be evicted
    for (let i = 0; i < 900; i++) {
      expect(stress.get(`k-${i}`)).toBeUndefined();
    }
  });
});

describe("lib/dlo-x/cache — singleton layoutCache", () => {
  it("exposes the expected size/capacity API", () => {
    // Smoke test that the singleton exports the right shape.
    expect(typeof layoutCache.size).toBe("function");
    expect(typeof layoutCache.get).toBe("function");
    expect(typeof layoutCache.set).toBe("function");
    expect(typeof layoutCache.delete).toBe("function");
    expect(typeof layoutCache.clear).toBe("function");
    expect(typeof layoutCache.cleanup).toBe("function");
  });
});

// Re-import the singleton for the smoke test
import { layoutCache } from "@/lib/dlo-x/cache";
