"use client";

type ObserverOptions = {
  rootMargin?: string;
  threshold?: number | number[];
};

type EntryCallback = (entry: IntersectionObserverEntry) => void;

interface SharedObserver {
  observer: IntersectionObserver;
  callbacks: WeakMap<Element, EntryCallback>;
}

const observerRegistry = new Map<string, SharedObserver>();

function getRegistryKey(options: ObserverOptions): string {
  const threshold = Array.isArray(options.threshold) ? options.threshold.join(",") : options.threshold ?? 0;
  return `${options.rootMargin ?? "0px"}|${threshold}`;
}

function getSharedObserver(options: ObserverOptions): SharedObserver | null {
  if (typeof window === "undefined" || !("IntersectionObserver" in window)) {
    return null;
  }

  const key = getRegistryKey(options);
  const existing = observerRegistry.get(key);
  if (existing) return existing;

  const callbacks = new WeakMap<Element, EntryCallback>();
  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        callbacks.get(entry.target)?.(entry);
      }
    },
    {
      rootMargin: options.rootMargin,
      threshold: options.threshold,
    },
  );

  const next = { observer, callbacks };
  observerRegistry.set(key, next);
  return next;
}

export function observeElement(target: Element, options: ObserverOptions, callback: EntryCallback): () => void {
  const shared = getSharedObserver(options);
  if (!shared) {
    callback({
      boundingClientRect: target.getBoundingClientRect(),
      intersectionRatio: 1,
      intersectionRect: target.getBoundingClientRect(),
      isIntersecting: true,
      rootBounds: null,
      target,
      time: performance.now(),
    } as IntersectionObserverEntry);
    return () => undefined;
  }

  shared.callbacks.set(target, callback);
  shared.observer.observe(target);
  return () => {
    shared.observer.unobserve(target);
    shared.callbacks.delete(target);
  };
}

