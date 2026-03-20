"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  type ReactNode,
} from "react";
import type {
  DecisionBatchResponse,
  DecisionRequest,
  DecisionResponse,
  PlacementOpportunity,
} from "@/lib/ad-orchestration";
import { getTrackingService } from "./TrackingService";

type DecisionBase = Partial<Omit<DecisionRequest, "requestId" | "opportunities">>;
type DecisionSubscriber = (decision: DecisionResponse | null) => void;

type DeferredDecision = {
  opportunity: PlacementOpportunity;
  promise: Promise<DecisionResponse | null>;
  resolve: (decision: DecisionResponse | null) => void;
  reject: (error: Error) => void;
};

const FLUSH_WINDOW_MS = 24;
const BASE_TIMEOUT_MS = 1500;
const PER_SLOT_TIMEOUT_MS = 220;
const MAX_TIMEOUT_MS = 4500;
const RETRY_DELAYS_MS = [180, 450, 900];

class DecisionTimeoutError extends Error {
  constructor(timeoutMs: number) {
    super(`Decision request timed out after ${timeoutMs}ms`);
    this.name = "DecisionTimeoutError";
  }
}

interface AdRuntimeContextValue {
  getDecision: (opportunity: PlacementOpportunity, signal?: AbortSignal) => Promise<DecisionResponse | null>;
  getCachedDecision: (slotId: string) => DecisionResponse | null;
  subscribeDecision: (slotId: string, subscriber: DecisionSubscriber) => () => void;
  prefetch: (opportunities: PlacementOpportunity[]) => void;
  track: ReturnType<typeof getTrackingService>;
}

const AdRuntimeContext = createContext<AdRuntimeContextValue | null>(null);

function buildClientHints(): DecisionRequest["client"] {
  const navigatorConnection = typeof navigator !== "undefined"
    ? (navigator as Navigator & { connection?: { effectiveType?: DecisionRequest["client"]["connection"]; saveData?: boolean } }).connection
    : undefined;

  return {
    viewportWidth: typeof window !== "undefined" ? window.innerWidth : undefined,
    viewportHeight: typeof window !== "undefined" ? window.innerHeight : undefined,
    dpr: typeof window !== "undefined" ? window.devicePixelRatio : undefined,
    connection: navigatorConnection?.effectiveType,
    saveData: navigatorConnection?.saveData,
    language: typeof navigator !== "undefined" ? navigator.language : undefined,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
  };
}

function mergeBase(parent: DecisionBase | undefined, child: DecisionBase | undefined): DecisionBase {
  return {
    ...parent,
    ...child,
    page: { ...parent?.page, ...child?.page },
    user: { ...parent?.user, ...child?.user },
    client: { ...parent?.client, ...child?.client },
    session: { ...parent?.session, ...child?.session },
  };
}

function createDeferredDecision(opportunity: PlacementOpportunity): DeferredDecision {
  let resolve!: (decision: DecisionResponse | null) => void;
  let reject!: (error: Error) => void;

  const promise = new Promise<DecisionResponse | null>((res, rej) => {
    resolve = res;
    reject = rej;
  });

  return {
    opportunity,
    promise,
    resolve,
    reject,
  };
}

function withAbortSignal<T>(promise: Promise<T>, signal?: AbortSignal): Promise<T> {
  if (!signal) return promise;
  if (signal.aborted) {
    return Promise.reject(new Error("Decision request aborted"));
  }

  return new Promise<T>((resolve, reject) => {
    const handleAbort = () => reject(new Error("Decision request aborted"));
    signal.addEventListener("abort", handleAbort, { once: true });

    promise.then(
      (value) => {
        signal.removeEventListener("abort", handleAbort);
        resolve(value);
      },
      (error) => {
        signal.removeEventListener("abort", handleAbort);
        reject(error);
      },
    );
  });
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export interface AdRuntimeProviderProps {
  baseRequest?: DecisionBase;
  initialDecisions?: DecisionResponse[];
  children: ReactNode;
}

export function AdRuntimeProvider({ baseRequest, initialDecisions = [], children }: AdRuntimeProviderProps) {
  const mergedBase = useMemo<DecisionBase>(() => mergeBase(undefined, baseRequest), [baseRequest]);
  const queuedRef = useRef<Map<string, DeferredDecision>>(new Map());
  const inflightRef = useRef<Map<string, DeferredDecision>>(new Map());
  const cacheRef = useRef<Map<string, DecisionResponse>>(new Map(initialDecisions.map((decision) => [decision.slotId, decision])));
  const subscribersRef = useRef<Map<string, Set<DecisionSubscriber>>>(new Map());
  const flushTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const track = useMemo(() => getTrackingService(), []);

  const publishDecision = useCallback((decision: DecisionResponse) => {
    cacheRef.current.set(decision.slotId, decision);
    const listeners = subscribersRef.current.get(decision.slotId);
    listeners?.forEach((subscriber) => subscriber(decision));
  }, []);

  useEffect(() => {
    for (const decision of initialDecisions) {
      publishDecision(decision);
    }
  }, [initialDecisions, publishDecision]);

  useEffect(() => {
    return () => {
      if (flushTimerRef.current) {
        clearTimeout(flushTimerRef.current);
        flushTimerRef.current = null;
      }

      queuedRef.current.forEach((entry) => entry.reject(new Error("Ad runtime provider unmounted")));
      inflightRef.current.forEach((entry) => entry.reject(new Error("Ad runtime provider unmounted")));
      queuedRef.current.clear();
      inflightRef.current.clear();
      subscribersRef.current.clear();
    };
  }, []);

  const getCachedDecision = useCallback((slotId: string) => {
    return cacheRef.current.get(slotId) ?? null;
  }, []);

  const subscribeDecision = useCallback((slotId: string, subscriber: DecisionSubscriber) => {
    const listeners = subscribersRef.current.get(slotId) ?? new Set<DecisionSubscriber>();
    listeners.add(subscriber);
    subscribersRef.current.set(slotId, listeners);

    const cached = cacheRef.current.get(slotId);
    if (cached) {
      subscriber(cached);
    }

    return () => {
      const current = subscribersRef.current.get(slotId);
      if (!current) return;
      current.delete(subscriber);
      if (current.size === 0) {
        subscribersRef.current.delete(slotId);
      }
    };
  }, []);

  const fetchDecisionBatch = useCallback(async (requestBody: DecisionRequest, timeoutMs: number) => {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);

    try {
      const response = await fetch("/api/ads/decision", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(requestBody),
        signal: controller.signal,
      });

      if (!response.ok) {
        throw new Error(`Decision request failed with status ${response.status}`);
      }

      const payload = (await response.json()) as DecisionBatchResponse;
      if (!Array.isArray(payload.decisions)) {
        throw new Error("Decision response payload is invalid");
      }

      return payload;
    } catch (error) {
      if (controller.signal.aborted) {
        throw new DecisionTimeoutError(timeoutMs);
      }
      throw (error instanceof Error ? error : new Error("Failed to resolve ad decisions"));
    } finally {
      clearTimeout(timeout);
    }
  }, []);

  const flushQueue = useCallback(async () => {
    if (queuedRef.current.size === 0) return;

    const batch = Array.from(queuedRef.current.values());
    queuedRef.current.clear();
    batch.forEach((entry) => inflightRef.current.set(entry.opportunity.slotId, entry));

    const requestBody: DecisionRequest = {
      requestId: `req_${Date.now().toString(36)}`,
      opportunities: batch.map((entry) => entry.opportunity),
      page: {
        ...mergedBase.page,
      },
      user: {
        ...mergedBase.user,
      },
      client: {
        ...buildClientHints(),
        ...mergedBase.client,
      },
      session: {
        id: mergedBase.session?.id ?? `sess_${Date.now().toString(36)}`,
        engagementScore: mergedBase.session?.engagementScore ?? 0.3,
        scrollDepth: mergedBase.session?.scrollDepth ?? 0,
      },
    };

    const timeoutMs = Math.min(MAX_TIMEOUT_MS, BASE_TIMEOUT_MS + (batch.length * PER_SLOT_TIMEOUT_MS));

    try {
      let payload: DecisionBatchResponse | null = null;
      let lastError: Error | null = null;

      for (let attempt = 0; attempt <= RETRY_DELAYS_MS.length; attempt += 1) {
        try {
          payload = await fetchDecisionBatch(requestBody, timeoutMs + (attempt * 350));
          break;
        } catch (error) {
          lastError = error instanceof Error ? error : new Error("Failed to resolve ad decisions");
          if (attempt === RETRY_DELAYS_MS.length) {
            throw lastError;
          }
          await sleep(RETRY_DELAYS_MS[attempt]);
        }
      }

      if (!payload) {
        throw lastError ?? new Error("Decision payload was empty");
      }

      const decisionMap = new Map(payload.decisions.map((decision) => [decision.slotId, decision]));

      for (const entry of batch) {
        const decision = decisionMap.get(entry.opportunity.slotId) ?? null;
        if (decision) {
          publishDecision(decision);
        }
        entry.resolve(decision);
        inflightRef.current.delete(entry.opportunity.slotId);
      }
    } catch (error) {
      const err = error instanceof Error ? error : new Error("Failed to resolve ad decisions");
      for (const entry of batch) {
        entry.reject(err);
        inflightRef.current.delete(entry.opportunity.slotId);
      }
    }
  }, [fetchDecisionBatch, mergedBase.client, mergedBase.page, mergedBase.session, mergedBase.user, publishDecision]);

  const scheduleFlush = useCallback(() => {
    if (flushTimerRef.current) return;
    flushTimerRef.current = setTimeout(() => {
      flushTimerRef.current = null;
      void flushQueue();
    }, FLUSH_WINDOW_MS);
  }, [flushQueue]);

  const getDecision = useCallback((opportunity: PlacementOpportunity, signal?: AbortSignal) => {
    const cached = cacheRef.current.get(opportunity.slotId);
    if (cached) {
      return Promise.resolve(cached);
    }

    const queued = queuedRef.current.get(opportunity.slotId);
    if (queued) {
      return withAbortSignal(queued.promise, signal);
    }

    const inflight = inflightRef.current.get(opportunity.slotId);
    if (inflight) {
      return withAbortSignal(inflight.promise, signal);
    }

    const next = createDeferredDecision(opportunity);
    queuedRef.current.set(opportunity.slotId, next);
    scheduleFlush();
    return withAbortSignal(next.promise, signal);
  }, [scheduleFlush]);

  const prefetch = useCallback((opportunities: PlacementOpportunity[]) => {
    for (const opportunity of opportunities) {
      if (cacheRef.current.has(opportunity.slotId)) continue;
      void getDecision(opportunity).catch(() => undefined);
    }
  }, [getDecision]);

  const value = useMemo<AdRuntimeContextValue>(() => ({
    getDecision,
    getCachedDecision,
    subscribeDecision,
    prefetch,
    track,
  }), [getCachedDecision, getDecision, prefetch, subscribeDecision, track]);

  return (
    <AdRuntimeContext.Provider value={value}>
      {children}
    </AdRuntimeContext.Provider>
  );
}

export function useAdRuntime() {
  const context = useContext(AdRuntimeContext);
  if (!context) {
    throw new Error("useAdRuntime must be used within AdRuntimeProvider");
  }

  return context;
}
