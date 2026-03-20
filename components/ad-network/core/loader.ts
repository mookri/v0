/**
 * Ad Engine v2 — Smart Loader
 * FIXES:
 *   BUG-01 defaultLoader ran new AdLoader() at module scope → SSR crash.
 *          Fix: lazy Proxy getter — only initialised in the browser.
 *   BUG-09 selectImageUrl accessed window.devicePixelRatio without SSR guard.
 *   BUG-12 ConnectionMonitor constructor read navigator.connection at module scope.
 *   BUG-15 ResourceCache.evict() was O(n log n) sort. Now O(1) Map-order eviction.
 */

import type { Ad, AdLayout, AdCreative } from './types';

export type LoadPriority = 'critical' | 'high' | 'normal' | 'low' | 'idle';
export type LoadStrategy = 'immediate' | 'lazy' | 'prefetch' | 'preload' | 'eager' | 'viewport';
export type ResourceType = 'image' | 'video' | 'script' | 'style' | 'font' | 'iframe' | 'html5';
export type ConnectionType = '4g' | '3g' | '2g' | 'slow-2g' | 'wifi' | 'ethernet' | 'unknown';

export interface LoaderConfig {
  rootMargin?: string;
  threshold?: number;
  maxRetries?: number;
  retryDelayMs?: number;
  concurrency?: number;
  respectSaveData?: boolean;
  connectionAware?: boolean;
  enableCache?: boolean;
  cacheTtlMs?: number;
  viewportLookahead?: number;
  enablePreconnect?: boolean;
  enableDnsPrefetch?: boolean;
  preferWebP?: boolean;
  preferHiDpi?: boolean;
}

export interface LoadRequest {
  id: string;
  url: string;
  type: ResourceType;
  priority: LoadPriority;
  strategy: LoadStrategy;
  ad?: Ad;
  container?: HTMLElement;
  onLoad?: (url: string, fromCache: boolean) => void;
  onError?: (url: string, error: Error) => void;
  onProgress?: (pct: number) => void;
}

export interface LoadResult {
  id: string;
  url: string;
  success: boolean;
  fromCache: boolean;
  loadTimeMs: number;
  byteSize?: number;
  error?: string;
  attempts: number;
}

const PRIORITY_ORDER: Record<LoadPriority, number> = {
  critical: 0, high: 1, normal: 2, low: 3, idle: 4,
};

class PriorityQueue<T extends { priority: LoadPriority }> {
  private items: T[] = [];
  enqueue(item: T): void {
    const idx = this.items.findIndex(i => PRIORITY_ORDER[i.priority] > PRIORITY_ORDER[item.priority]);
    if (idx === -1) this.items.push(item);
    else this.items.splice(idx, 0, item);
  }
  dequeue(): T | undefined { return this.items.shift(); }
  remove(predicate: (item: T) => boolean): void { this.items = this.items.filter(i => !predicate(i)); }
  get length(): number { return this.items.length; }
  isEmpty(): boolean { return this.items.length === 0; }
  peek(): T | undefined { return this.items[0]; }
}

interface CacheEntry {
  url: string;
  blob?: Blob;
  objectUrl?: string;
  timestamp: number;
  byteSize: number;
}

// BUG-15 FIX: O(1) eviction. Map preserves insertion order — first key = oldest.
class ResourceCache {
  private store = new Map<string, CacheEntry>();
  private ttl: number;
  private maxEntries: number;

  constructor(ttlMs = 300_000, maxEntries = 200) {
    this.ttl = ttlMs;
    this.maxEntries = maxEntries;
  }

  set(url: string, entry: Omit<CacheEntry, 'timestamp'>): void {
    // O(1): delete first key (oldest) without sorting
    if (this.store.size >= this.maxEntries) {
      const firstKey = this.store.keys().next().value;
      if (firstKey !== undefined) {
        const oldest = this.store.get(firstKey);
        if (oldest?.objectUrl) URL.revokeObjectURL(oldest.objectUrl);
        this.store.delete(firstKey);
      }
    }
    this.store.delete(url); // promote to end of insertion order
    this.store.set(url, { ...entry, timestamp: Date.now() });
  }

  get(url: string): CacheEntry | null {
    const entry = this.store.get(url);
    if (!entry) return null;
    if (Date.now() - entry.timestamp > this.ttl) {
      if (entry.objectUrl) URL.revokeObjectURL(entry.objectUrl);
      this.store.delete(url);
      return null;
    }
    // LRU: re-insert to move to end of order
    this.store.delete(url);
    this.store.set(url, entry);
    return entry;
  }

  has(url: string): boolean { return !!this.get(url); }

  clear(): void {
    for (const entry of this.store.values()) {
      if (entry.objectUrl) URL.revokeObjectURL(entry.objectUrl);
    }
    this.store.clear();
  }

  getStats(): { size: number; totalBytes: number } {
    const totalBytes = Array.from(this.store.values()).reduce((s, e) => s + e.byteSize, 0);
    return { size: this.store.size, totalBytes };
  }
}

// BUG-12 FIX: ConnectionMonitor is only instantiated inside AdLoader,
// which is only instantiated via the lazy getter (browser-only).
class ConnectionMonitor {
  private _type: ConnectionType = 'unknown';
  private _saveData = false;
  private _rtt = 0;
  private listeners: Array<(type: ConnectionType) => void> = [];

  constructor() {
    this._update();
    const conn = (navigator as unknown as Record<string, { addEventListener?: Function }> ).connection;
    conn?.addEventListener?.('change', () => { this._update(); this.listeners.forEach(l => l(this._type)); });
  }

  private _update(): void {
    const conn = (navigator as unknown as Record<string, Record<string, unknown>>).connection;
    if (!conn) return;
    this._type = (conn.effectiveType as ConnectionType) ?? 'unknown';
    this._saveData = (conn.saveData as boolean) ?? false;
    this._rtt = (conn.rtt as number) ?? 0;
  }

  get type(): ConnectionType { return this._type; }
  get saveData(): boolean { return this._saveData; }
  get rtt(): number { return this._rtt; }

  isSlow(): boolean { return this._type === '2g' || this._type === 'slow-2g' || this._rtt > 300; }
  isFast(): boolean { return this._type === '4g' || this._type === 'wifi' || this._type === 'ethernet'; }

  onChange(cb: (type: ConnectionType) => void): () => void {
    this.listeners.push(cb);
    return () => { this.listeners = this.listeners.filter(l => l !== cb); };
  }
}

export class AdLoader {
  private config: Required<LoaderConfig>;
  private queue: PriorityQueue<LoadRequest> = new PriorityQueue();
  private cache: ResourceCache;
  private connection: ConnectionMonitor;
  private observers: Map<string, IntersectionObserver> = new Map();
  private activeLoads = 0;
  private results: Map<string, LoadResult> = new Map();
  private preconnectedDomains = new Set<string>();
  private readonly supportsWebP: Promise<boolean>;

  constructor(config: LoaderConfig = {}) {
    this.config = {
      rootMargin: config.rootMargin ?? '300px',
      threshold: config.threshold ?? 0.01,
      maxRetries: config.maxRetries ?? 3,
      retryDelayMs: config.retryDelayMs ?? 500,
      concurrency: config.concurrency ?? 6,
      respectSaveData: config.respectSaveData ?? true,
      connectionAware: config.connectionAware ?? true,
      enableCache: config.enableCache ?? true,
      cacheTtlMs: config.cacheTtlMs ?? 300_000,
      viewportLookahead: config.viewportLookahead ?? 400,
      enablePreconnect: config.enablePreconnect ?? true,
      enableDnsPrefetch: config.enableDnsPrefetch ?? true,
      preferWebP: config.preferWebP ?? true,
      preferHiDpi: config.preferHiDpi ?? true,
    };
    this.cache = new ResourceCache(this.config.cacheTtlMs);
    this.connection = new ConnectionMonitor();
    this.supportsWebP = this._detectWebP();
  }

  private _detectWebP(): Promise<boolean> {
    return new Promise(resolve => {
      const img = new Image();
      img.onload = () => resolve(img.width === 1);
      img.onerror = () => resolve(false);
      img.src = 'data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAQAcJYgCdAEO/gHOAAA=';
    });
  }

  // BUG-09 FIX: window.devicePixelRatio gated behind SSR guard
  async selectImageUrl(creative: AdCreative): Promise<string> {
    const isSlowConn = this.connection.isSlow();
    const saveData = this.connection.saveData && this.config.respectSaveData;
    const webpSupported = this.config.preferWebP && await this.supportsWebP;
    const hiDpi = this.config.preferHiDpi &&
      typeof window !== 'undefined' &&
      window.devicePixelRatio > 1.5;

    if (saveData) return creative.imageUrl ?? '';
    if (webpSupported && creative.imageUrlWebp) return creative.imageUrlWebp;
    if (hiDpi && creative.imageUrlHiDpi && !isSlowConn) return creative.imageUrlHiDpi;
    return creative.imageUrl ?? '';
  }

  selectVideoUrl(creative: AdCreative): string {
    if (this.connection.isSlow()) return creative.videoUrl ?? '';
    return creative.videoUrlHls ?? creative.videoUrl ?? '';
  }

  preconnect(url: string): void {
    if (!this.config.enablePreconnect) return;
    try {
      const domain = new URL(url).origin;
      if (this.preconnectedDomains.has(domain)) return;
      this.preconnectedDomains.add(domain);
      const link = document.createElement('link');
      link.rel = 'preconnect'; link.href = domain; link.crossOrigin = 'anonymous';
      document.head.appendChild(link);
    } catch { }
  }

  dnsPrefetch(url: string): void {
    if (!this.config.enableDnsPrefetch) return;
    try {
      const domain = new URL(url).hostname;
      if (document.querySelector(`link[rel="dns-prefetch"][href="//${domain}"]`)) return;
      const link = document.createElement('link');
      link.rel = 'dns-prefetch'; link.href = `//${domain}`;
      document.head.appendChild(link);
    } catch { }
  }

  addPreloadHint(url: string, as: 'image' | 'script' | 'style' | 'fetch'): void {
    if (document.querySelector(`link[rel="preload"][href="${url}"]`)) return;
    const link = document.createElement('link');
    link.rel = 'preload'; link.href = url; link.setAttribute('as', as);
    if (as === 'fetch') link.crossOrigin = 'anonymous';
    document.head.appendChild(link);
  }

  private async _loadResource(req: LoadRequest, attempt = 1): Promise<LoadResult> {
    const start = performance.now();
    if (this.config.enableCache && this.cache.has(req.url)) {
      const entry = this.cache.get(req.url)!;
      req.onLoad?.(req.url, true);
      return { id: req.id, url: req.url, success: true, fromCache: true, loadTimeMs: performance.now() - start, byteSize: entry.byteSize, attempts: attempt };
    }
    try {
      let byteSize = 0;
      if (req.type === 'image') {
        await new Promise<void>((resolve, reject) => {
          const img = new Image();
          img.onload = () => resolve();
          img.onerror = () => reject(new Error(`Failed: ${req.url}`));
          img.src = req.url;
        });
      } else if (req.type === 'video') {
        await new Promise<void>((resolve, reject) => {
          const video = document.createElement('video');
          video.oncanplay = () => resolve();
          video.onerror = () => reject(new Error(`Failed: ${req.url}`));
          video.preload = 'metadata'; video.src = req.url;
        });
      } else if (req.type === 'script') {
        await new Promise<void>((resolve, reject) => {
          const script = document.createElement('script');
          script.onload = () => resolve();
          script.onerror = () => reject(new Error(`Failed: ${req.url}`));
          script.src = req.url; script.async = true;
          document.head.appendChild(script);
        });
      } else {
        const resp = await fetch(req.url, { credentials: 'omit' });
        if (!resp.ok) throw new Error(`HTTP ${resp.status}: ${req.url}`);
        const blob = await resp.blob();
        byteSize = blob.size;
        if (this.config.enableCache) {
          const objectUrl = URL.createObjectURL(blob);
          this.cache.set(req.url, { url: req.url, blob, objectUrl, byteSize });
        }
      }
      req.onLoad?.(req.url, false);
      return { id: req.id, url: req.url, success: true, fromCache: false, loadTimeMs: performance.now() - start, byteSize, attempts: attempt };
    } catch (e) {
      if (attempt < this.config.maxRetries) {
        await new Promise(r => setTimeout(r, this.config.retryDelayMs * Math.pow(2, attempt - 1)));
        return this._loadResource(req, attempt + 1);
      }
      const err = e instanceof Error ? e : new Error(String(e));
      req.onError?.(req.url, err);
      return { id: req.id, url: req.url, success: false, fromCache: false, loadTimeMs: performance.now() - start, error: err.message, attempts: attempt };
    }
  }

  private async _processQueue(): Promise<void> {
    while (!this.queue.isEmpty() && this.activeLoads < this.config.concurrency) {
      const req = this.queue.dequeue();
      if (!req) break;
      this.activeLoads++;
      this._loadResource(req).then(result => {
        this.results.set(result.id, result);
        this.activeLoads--;
        this._processQueue();
      });
    }
  }

  enqueue(req: LoadRequest): void {
    if (this.config.enablePreconnect) this.preconnect(req.url);
    this.queue.enqueue(req);
    this._processQueue();
  }

  lazyLoad(req: LoadRequest, target: HTMLElement): () => void {
    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            this.enqueue(req);
            observer.unobserve(target);
            this.observers.delete(req.id);
          }
        }
      },
      { rootMargin: this.config.rootMargin, threshold: this.config.threshold },
    );
    observer.observe(target);
    this.observers.set(req.id, observer);
    return () => { observer.unobserve(target); this.observers.delete(req.id); };
  }

  prefetch(req: LoadRequest): void {
    const run = () => this.enqueue({ ...req, priority: 'idle' });
    if (typeof window !== 'undefined' && 'requestIdleCallback' in window) {
      (window as unknown as { requestIdleCallback: Function }).requestIdleCallback(run, { timeout: 5000 });
    } else {
      setTimeout(run, 2000);
    }
  }

  cancel(id: string): void {
    this.queue.remove(r => r.id === id);
    const obs = this.observers.get(id);
    if (obs) { obs.disconnect(); this.observers.delete(id); }
  }

  getResult(id: string): LoadResult | undefined { return this.results.get(id); }
  getCacheStats(): { size: number; totalBytes: number } { return this.cache.getStats(); }
  clearCache(): void { this.cache.clear(); }

  async loadAdCreatives(ad: Ad): Promise<void> {
    if (!ad.creative) return;
    const creative = ad.creative;
    [creative.imageUrl, creative.videoUrl, creative.html5BundleUrl, creative.ampHtmlUrl]
      .filter(Boolean).forEach(u => { this.preconnect(u!); this.dnsPrefetch(u!); });

    const priority: LoadPriority = (ad.priority ?? 0) >= 8 ? 'critical' : (ad.priority ?? 0) >= 5 ? 'high' : 'normal';

    if (creative.imageUrl) {
      const url = await this.selectImageUrl(creative);
      if (url) this.enqueue({ id: `${ad.id}_image`, url, type: 'image', priority, strategy: 'preload' });
    }
    if (creative.videoUrl && !this.connection.saveData) {
      const url = this.selectVideoUrl(creative);
      if (url) this.enqueue({ id: `${ad.id}_video`, url, type: 'video', priority: 'low', strategy: 'lazy' });
    }
    if (creative.html5BundleUrl) this.addPreloadHint(creative.html5BundleUrl, 'fetch');
    creative.trackingScripts?.forEach((script, i) => {
      this.enqueue({ id: `${ad.id}_tracking_${i}`, url: script, type: 'script', priority: 'low', strategy: 'prefetch' });
    });
  }

  async preloadAboveFold(ads: Ad[], maxAds = 3): Promise<void> {
    await Promise.all(ads.slice(0, maxAds).map(ad => this.loadAdCreatives(ad)));
  }

  predictAndPreload(ad: Ad, target: HTMLElement): () => void {
    return this.lazyLoad(
      { id: `${ad.id}_predictive`, url: ad.creative?.imageUrl ?? '', type: 'image', priority: 'normal', strategy: 'viewport' },
      target,
    );
  }

  getRecommendedStrategy(layout: AdLayout): LoadStrategy {
    if (this.connection.saveData && this.config.respectSaveData) return 'lazy';
    if (this.connection.isSlow()) return 'lazy';
    if (['hero', 'hero_cinematic', 'interstitial_full'].includes(layout)) return 'preload';
    return 'viewport';
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// BUG-01 FIX: defaultLoader was `export const defaultLoader = new AdLoader()`
// at module scope, calling new Image() and navigator.connection during SSR.
//
// Fix: Lazy Proxy — the real AdLoader is only constructed on first property
// access, which can only happen in browser code after hydration.
// ─────────────────────────────────────────────────────────────────────────────

let _defaultLoader: AdLoader | null = null;

const DEFAULT_CONFIG: LoaderConfig = {
  rootMargin: '400px', threshold: 0.01, maxRetries: 3, concurrency: 6,
  enableCache: true, cacheTtlMs: 300_000, respectSaveData: true,
  connectionAware: true, preferWebP: true, preferHiDpi: true,
  enablePreconnect: true, enableDnsPrefetch: true,
};

export const defaultLoader: AdLoader = new Proxy({} as AdLoader, {
  get(_target, prop: string | symbol) {
    if (typeof window === 'undefined') {
      // SSR: no-op proxy — prevents crashes in server components
      return typeof prop === 'string' && ['enqueue','lazyLoad','prefetch','cancel','clearCache'].includes(prop)
        ? () => undefined
        : undefined;
    }
    if (!_defaultLoader) _defaultLoader = new AdLoader(DEFAULT_CONFIG);
    const value = (_defaultLoader as unknown as Record<string | symbol, unknown>)[prop];
    return typeof value === 'function' ? (value as Function).bind(_defaultLoader) : value;
  },
});
