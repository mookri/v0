"use client";

import type { DecisionResponse, TrackingBatchPayload, TrackingEventPayload } from "@/lib/ad-orchestration";

const MAX_BATCH_SIZE = 20;
const FLUSH_INTERVAL_MS = 2000;

class TrackingService {
  private readonly queue: TrackingEventPayload[] = [];
  private readonly seenKeys = new Set<string>();
  private flushTimer: ReturnType<typeof setTimeout> | null = null;

  record(decision: DecisionResponse, type: TrackingEventPayload["type"], extra: Partial<TrackingEventPayload> = {}) {
    if (!decision.ad) return;

    const idempotencyKey =
      type === "impression"
        ? decision.tracking.impressionKey
        : type === "viewable"
          ? decision.tracking.viewableKey
          : type === "click"
            ? decision.tracking.clickKey
            : decision.tracking.renderKey;

    if (this.seenKeys.has(idempotencyKey)) return;
    if (type === "render" || type === "impression" || type === "viewable") {
      this.seenKeys.add(idempotencyKey);
    }

    const event: TrackingEventPayload = {
      type,
      requestId: decision.requestId,
      decisionId: decision.decisionId,
      auctionId: decision.tracking.auctionId,
      adId: decision.ad.id,
      creativeId: decision.ad.creative?.id,
      placement: decision.placement,
      layout: decision.layout,
      timestamp: Date.now(),
      idempotencyKey,
      experimentAssignments: decision.experimentAssignments,
      connectionType: typeof navigator !== "undefined" ? (navigator as Navigator & { connection?: { effectiveType?: string } }).connection?.effectiveType : undefined,
      customData: {
        clearingPrice: decision.clearingPrice,
        floorApplied: decision.floorApplied,
        rankScore: decision.rankScore,
        ...extra.customData,
      },
      ...extra,
    };

    this.queue.push(event);
    if (this.queue.length >= MAX_BATCH_SIZE) {
      void this.flush("queue");
      return;
    }

    this.ensureTimer();
  }

  private ensureTimer() {
    if (this.flushTimer) return;
    this.flushTimer = setTimeout(() => {
      this.flushTimer = null;
      void this.flush("queue");
    }, FLUSH_INTERVAL_MS);
  }

  async flush(source: TrackingBatchPayload["source"] = "fetch") {
    if (this.queue.length === 0) return;

    const batch: TrackingBatchPayload = {
      batchId: `trk_${Date.now().toString(36)}`,
      events: this.queue.splice(0, this.queue.length),
      sentAt: Date.now(),
      source,
    };

    if (source === "beacon" && typeof navigator !== "undefined" && navigator.sendBeacon) {
      const ok = navigator.sendBeacon(
        "/api/ads/track-beacon",
        new Blob([JSON.stringify(batch)], { type: "application/json" }),
      );
      if (ok) return;
    }

    await fetch("/api/ads/track", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(batch),
      keepalive: true,
    });
  }
}

let singleton: TrackingService | null = null;

export function getTrackingService(): TrackingService {
  if (!singleton) {
    singleton = new TrackingService();
    if (typeof window !== "undefined") {
      window.addEventListener("pagehide", () => {
        void singleton?.flush("beacon");
      });
      document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "hidden") {
          void singleton?.flush("beacon");
        }
      });
    }
  }

  return singleton;
}

