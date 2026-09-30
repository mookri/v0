/* ═══════════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  tracking.ts
   Rich event pipeline · Batched queue · Multi-touch attribution
   IAB MRC viewability · Session syncing · Beacon unload · 3rd-party pixels
   ═══════════════════════════════════════════════════════════════════════════════ */

import { Ad, AdEvent, AdEventType, AdLayout, AdPlacement, TrackingBatch } from "./types";
import { recordSpend } from "./pricing";
import { recordImpression } from "./frequency";

// ─── Session & IDs ────────────────────────────────────────────────────────────

// BUG-04 FIX: _sessionId is per-browser-session (sessionStorage) — OK.
// _sequenceCounter was module-level, incrementing globally across all users
// in the same Node process during SSR. Fixed: now derived from sessionStorage
// so each browser session has its own monotonic sequence.
let _sessionId: string | null = null;
let _sdkVersion = "2.0.0";

function getSequenceNumber(): number {
  if (typeof window === "undefined") return 0;
  try {
    const key = "ad_seq_v2";
    const n = parseInt(sessionStorage.getItem(key) ?? "0", 10) + 1;
    sessionStorage.setItem(key, String(n));
    return n;
  } catch { return 0; }
}

export function setSdkVersion(v: string) { _sdkVersion = v; }

function getSessionId(): string {
  if (typeof window === "undefined") return "ssr";
  if (_sessionId) return _sessionId;

  let id = sessionStorage.getItem("ad_session_v2");
  if (!id) {
    id = `s_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
    sessionStorage.setItem("ad_session_v2", id);
  }
  _sessionId = id;
  return id;
}

export function getAnonymousId(): string {
  if (typeof window === "undefined") return "anon_ssr";
  try {
    let id = localStorage.getItem("ad_anon_id");
    if (!id) {
      id = `anon_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
      localStorage.setItem("ad_anon_id", id);
    }
    return id;
  } catch { return "anon_fallback"; }
}

// ─── Event Queue + Batching ───────────────────────────────────────────────────

export interface TrackingConfig {
  batchSize: number;
  flushIntervalMs: number;
  maxRetries: number;
  endpoint: string;
  beaconEndpoint: string;
}

const DEFAULT_CONFIG: TrackingConfig = {
  batchSize: 30,
  flushIntervalMs: 5_000,
  maxRetries: 3,
  endpoint: "/api/ads/track",
  beaconEndpoint: "/api/ads/track-beacon",
};

let _config = { ...DEFAULT_CONFIG };
export function configureTracking(cfg: Partial<TrackingConfig>) {
  _config = { ..._config, ...cfg };
}

let _queue: AdEvent[] = [];
let _flushTimer: ReturnType<typeof setInterval> | null = null;
let _flushRetryQueues: { events: AdEvent[]; retries: number }[] = [];

function ensureFlushTimer() {
  if (typeof window === "undefined") return;
  if (_flushTimer) return;

  _flushTimer = setInterval(flushQueue, _config.flushIntervalMs);

  window.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") flushBeacon();
  });
  window.addEventListener("pagehide", () => flushBeacon(), { once: true });
}

function pushEvent(event: AdEvent) {
  event.sequenceNumber = getSequenceNumber(); // BUG-04 FIX: per-session counter
  _queue.push(event);
  ensureFlushTimer();
  if (_queue.length >= _config.batchSize) flushQueue();
}

export function flushQueue() {
  if (!_queue.length) return;
  const batch = [..._queue];
  _queue = [];
  sendBatch({ batchId: `b_${Date.now()}`, events: batch, sentAt: Date.now(), source: "queue" });
}

function flushBeacon() {
  if (!_queue.length) return;
  const batch = [..._queue];
  _queue = [];

  // BUG-17 FIX: was inconsistent — flushQueue() skipped sending in dev but
  // flushBeacon() (triggered on page unload/visibilitychange) still sent.
  // Align: dev mode logs and skips sending for both flush paths.
  if (process.env.NODE_ENV !== "production") {
    console.groupCollapsed(
      `%c[AdEngine v2] Beacon flush (dev) — ${batch.length} events`,
      "color: #6366f1; font-weight: bold"
    );
    batch.forEach(e => console.log(`  beacon ${e.type} | ${e.adId}`));
    console.groupEnd();
    return; // Don't send in dev
  }

  try {
    if (typeof navigator !== "undefined" && navigator.sendBeacon) {
      const success = navigator.sendBeacon(
        _config.beaconEndpoint,
        new Blob([JSON.stringify({ batchId: `b_beacon_${Date.now()}`, events: batch, sentAt: Date.now(), source: "beacon" })], { type: "application/json" })
      );
      if (!success) throw new Error("Beacon rejected");
    } else {
      throw new Error("Beacon not available");
    }
  } catch {
    sendBatch({ batchId: `b_fallback_${Date.now()}`, events: batch, sentAt: Date.now(), retryCount: 0, source: "fetch" });
  }
}

async function sendBatch(batch: TrackingBatch, retryCount = 0): Promise<void> {
  if (process.env.NODE_ENV !== "production") {
    console.groupCollapsed(
      `%c[AdEngine v2] Batch flush — ${batch.events.length} events`,
      "color: #6366f1; font-weight: bold"
    );
    batch.events.forEach(e => {
      const emoji = ({ impression:"👁", click:"🖱", viewable:"✅", conversion:"🎯", video_start:"▶", video_complete:"🏁", skip:"⏭" } as Record<string, string>)[e.type] ?? "•";
      console.log(`  ${emoji} ${e.type.padEnd(14)} | ${e.adId.padEnd(24)} | rev: ${(e.revenueCredit ?? 0).toFixed(4)}`);
    });
    console.groupEnd();
    return; // Don't send in dev
  }

  try {
    const res = await fetch(_config.endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(batch),
      keepalive: true,
    });

    if (!res.ok && retryCount < _config.maxRetries) {
      const backoff = Math.pow(2, retryCount) * 500;
      setTimeout(() => sendBatch(batch, retryCount + 1), backoff);
    }
  } catch {
    if (retryCount < _config.maxRetries) {
      const backoff = Math.pow(2, retryCount) * 1000;
      setTimeout(() => sendBatch(batch, retryCount + 1), backoff);
    }
  }
}

// ─── Core Track ───────────────────────────────────────────────────────────────

export interface TrackExtra {
  viewabilityRatio?: number;
  viewabilityMs?: number;
  videoProgress?: number;
  interactionType?: string;
  xCoord?: number;
  yCoord?: number;
  elementId?: string;
  customData?: Record<string, unknown>;
  generatedRevenue?: number;
  qualityScore?: number;
}

export function track(
  type: AdEventType,
  ad: Ad,
  placement: AdPlacement,
  layout: AdLayout,
  extra?: TrackExtra
): void {
  const { revenue, withinBudget } = recordSpend(ad, type, {
    viewabilityRatio: extra?.viewabilityRatio,
    videoProgress: extra?.videoProgress,
    generatedRevenue: extra?.generatedRevenue,
    qualityScore: extra?.qualityScore,
  });

  const event: AdEvent = {
    type,
    adId: ad.id,
    placement,
    layout,
    variant: ad.experiment?.variant,
    experimentKey: ad.experiment?.key,
    sessionId: getSessionId(),
    anonymousId: getAnonymousId(),
    timestamp: Date.now(),
    viewabilityRatio: extra?.viewabilityRatio,
    viewabilityMs: extra?.viewabilityMs,
    videoProgress: extra?.videoProgress,
    revenueCredit: revenue,
    currencyCode: ad.budget?.currencyCode ?? "INR",
    sdkVersion: _sdkVersion,
    interactionType: extra?.interactionType,
    xCoord: extra?.xCoord,
    yCoord: extra?.yCoord,
    elementId: extra?.elementId,
    customData: extra?.customData,
  };

  // Enrich with device context
  if (typeof window !== "undefined") {
    event.pageUrl = window.location.href;
    event.pageTitle = document.title;
    event.referrer = document.referrer || undefined;
    event.userAgent = navigator.userAgent;
  }

  pushEvent(event);

  // Fire third-party pixels
  fireThirdPartyPixels(ad, type);

  if (!withinBudget && process.env.NODE_ENV !== "production") {
    console.warn(`[AdEngine] Ad "${ad.id}" budget exhausted — will be suppressed next load.`);
  }
}

// ─── Third-party pixels ───────────────────────────────────────────────────────

function fireThirdPartyPixels(ad: Ad, eventType: AdEventType): void {
  if (typeof window === "undefined") return;

  // Legacy single pixels
  if (eventType === "impression" && ad.impressionPixel) firePxl(ad.impressionPixel);
  if (eventType === "click" && ad.clickPixel) firePxl(ad.clickPixel);
  if (eventType === "viewable" && ad.viewabilityPixel) firePxl(ad.viewabilityPixel);
  if (eventType === "conversion" && ad.conversionPixel) firePxl(ad.conversionPixel);

  // Multi-event pixels
  ad.thirdPartyPixels?.forEach(px => {
    if (px.event === eventType) firePxl(px.url);
  });
}

function firePxl(url: string): void {
  try {
    const img = new Image();
    img.src = url.replace("${ts}", String(Date.now()));
    img.width = 1; img.height = 1;
    img.style.display = "none";
    document.body?.appendChild(img);
    setTimeout(() => img.remove(), 5000);
  } catch { /**/ }
}

// ─── Convenience wrappers ─────────────────────────────────────────────────────

const lastClickTime = new Map<string, number>();
const CLICK_DEBOUNCE_MS = 500;
const lastImpressionTime = new Map<string, number>();
const IMPRESSION_DEBOUNCE_MS = 200;

export function trackImpression(ad: Ad, placement: AdPlacement, layout: AdLayout): void {
  const now = Date.now();
  const lastImp = lastImpressionTime.get(ad.id) ?? 0;
  if (now - lastImp < IMPRESSION_DEBOUNCE_MS) return;
  lastImpressionTime.set(ad.id, now);

  // Record frequency
  const fp = ad.frequencyProfile;
  if (fp) {
    if (fp.daily) recordImpression(ad.id, fp.daily.windowSeconds);
    if (fp.hourly) recordImpression(ad.id, fp.hourly.windowSeconds);
    if (fp.lifetime) recordImpression(ad.id, fp.lifetime.windowSeconds);
  } else if (ad.frequencyCap) {
    recordImpression(ad.id, ad.frequencyCap.windowSeconds);
  }

  track("impression", ad, placement, layout);
}

export function trackClick(
  ad: Ad,
  placement: AdPlacement,
  layout: AdLayout,
  coords?: { x: number; y: number }
): void {
  const now = Date.now();
  if (now - (lastClickTime.get(ad.id) ?? 0) < CLICK_DEBOUNCE_MS) return;
  lastClickTime.set(ad.id, now);
  track("click", ad, placement, layout, { xCoord: coords?.x, yCoord: coords?.y });
}

export function trackViewable(ad: Ad, placement: AdPlacement, layout: AdLayout, ratio: number, visibleMs: number): void {
  track("viewable", ad, placement, layout, { viewabilityRatio: ratio, viewabilityMs: visibleMs });
}

export function trackConversion(ad: Ad, placement: AdPlacement, layout: AdLayout, revenue?: number): void {
  track("conversion", ad, placement, layout, { generatedRevenue: revenue });
}

export function trackVideoEvent(ad: Ad, placement: AdPlacement, layout: AdLayout, videoEvent: AdEventType, progress: number): void {
  track(videoEvent, ad, placement, layout, { videoProgress: progress });
}

export function trackEngagement(ad: Ad, placement: AdPlacement, layout: AdLayout, interactionType: string): void {
  track("hover", ad, placement, layout, { interactionType });
}

// ─── Viewability Observer (IAB MRC + GroupM) ──────────────────────────────────

export type ViewabilityStandard = "iab_mrc" | "groupm" | "custom";

export interface ViewabilityConfig {
  standard?: ViewabilityStandard;
  minRatio?: number;         // Default: 0.5 (IAB MRC)
  minMs?: number;            // Default: 1000ms (IAB MRC)
  // GroupM: 100% in-view for 1s for display, 50% for 2s for video
}

const VIEWABILITY_STANDARDS: Record<ViewabilityStandard, { minRatio: number; minMs: number }> = {
  iab_mrc: { minRatio: 0.5, minMs: 1000 },
  groupm:  { minRatio: 1.0, minMs: 1000 },
  custom:  { minRatio: 0.5, minMs: 500 },
};

export interface ViewabilityObserverOptions {
  ad: Ad;
  placement: AdPlacement;
  layout: AdLayout;
  element: HTMLElement;
  config?: ViewabilityConfig;
  onViewable?: (ratio: number, durationMs: number) => void;
  onPartialView?: (ratio: number) => void;
}

export function observeViewability({
  ad, placement, layout, element, config: cfg, onViewable, onPartialView,
}: ViewabilityObserverOptions): () => void {
  if (typeof window === "undefined" || !("IntersectionObserver" in window)) return () => {};

  const standard = cfg?.standard ?? "iab_mrc";
  const defaults = VIEWABILITY_STANDARDS[standard];
  const minRatio = cfg?.minRatio ?? defaults.minRatio;
  const minMs = cfg?.minMs ?? defaults.minMs;

  let timer: ReturnType<typeof setTimeout> | null = null;
  let fired = false;
  let enterTime = 0;
  let partialTimer: ReturnType<typeof setTimeout> | null = null;

  const obs = new IntersectionObserver(
    ([entry]) => {
      if (fired) return;

      if (entry.intersectionRatio >= minRatio) {
        enterTime = Date.now();
        timer = setTimeout(() => {
          if (fired) return;
          fired = true;
          const durationMs = Date.now() - enterTime;
          trackViewable(ad, placement, layout, entry.intersectionRatio, durationMs);
          onViewable?.(entry.intersectionRatio, durationMs);
          obs.disconnect();
        }, minMs);
      } else {
        if (timer) { clearTimeout(timer); timer = null; }

        // Partial view tracking
        if (entry.intersectionRatio > 0.1) {
          onPartialView?.(entry.intersectionRatio);
        }
      }
    },
    { threshold: [0, 0.1, 0.25, 0.5, 0.75, 1.0] }
  );

  obs.observe(element);

  return () => {
    obs.disconnect();
    if (timer) clearTimeout(timer);
    if (partialTimer) clearTimeout(partialTimer);
  };
}

// ─── Multi-touch attribution ──────────────────────────────────────────────────

export type AttributionModel =
  | "last_touch"    // All credit to last ad before conversion
  | "first_touch"   // All credit to first touchpoint
  | "linear"        // Equal credit across all touchpoints
  | "time_decay"    // More credit to recent touchpoints
  | "position"      // 40% first, 40% last, 20% middle
  | "data_driven";  // ML-optimised (requires external model)

export interface TouchPoint {
  adId: string;
  placement: AdPlacement;
  layout: AdLayout;
  eventType: AdEventType;
  timestamp: number;
  revenue?: number;
}

const TOUCHPOINTS_KEY = "ad_touchpoints_v2";
const MAX_TOUCHPOINTS = 50;

export function recordTouchpoint(ad: Ad, placement: AdPlacement, layout: AdLayout, eventType: AdEventType): void {
  if (typeof window === "undefined") return;
  try {
    const raw = sessionStorage.getItem(TOUCHPOINTS_KEY);
    const touchpoints: TouchPoint[] = raw ? JSON.parse(raw) : [];
    touchpoints.push({
      adId: ad.id, placement, layout, eventType,
      timestamp: Date.now(),
    });
    // Keep last MAX_TOUCHPOINTS
    const trimmed = touchpoints.slice(-MAX_TOUCHPOINTS);
    sessionStorage.setItem(TOUCHPOINTS_KEY, JSON.stringify(trimmed));
  } catch { /**/ }
}

export function getAttributionCredits(
  model: AttributionModel = "last_touch",
  conversionRevenue = 0
): Record<string, number> {
  if (typeof window === "undefined") return {};

  try {
    const raw = sessionStorage.getItem(TOUCHPOINTS_KEY);
    const touchpoints: TouchPoint[] = raw ? JSON.parse(raw) : [];

    if (!touchpoints.length) return {};

    const credits: Record<string, number> = {};

    switch (model) {
      case "last_touch": {
        const last = touchpoints[touchpoints.length - 1];
        credits[last.adId] = conversionRevenue;
        break;
      }

      case "first_touch": {
        credits[touchpoints[0].adId] = conversionRevenue;
        break;
      }

      case "linear": {
        const share = conversionRevenue / touchpoints.length;
        touchpoints.forEach(tp => {
          credits[tp.adId] = (credits[tp.adId] ?? 0) + share;
        });
        break;
      }

      case "time_decay": {
        const now = Date.now();
        const weights = touchpoints.map(tp => Math.exp(-(now - tp.timestamp) / 3_600_000));
        const totalWeight = weights.reduce((a, b) => a + b, 0);
        touchpoints.forEach((tp, i) => {
          credits[tp.adId] = (credits[tp.adId] ?? 0) + (conversionRevenue * weights[i] / totalWeight);
        });
        break;
      }

      case "position": {
        if (touchpoints.length === 1) {
          credits[touchpoints[0].adId] = conversionRevenue;
        } else {
          const firstShare = conversionRevenue * 0.4;
          const lastShare = conversionRevenue * 0.4;
          const middleShare = conversionRevenue * 0.2 / Math.max(1, touchpoints.length - 2);
          credits[touchpoints[0].adId] = (credits[touchpoints[0].adId] ?? 0) + firstShare;
          credits[touchpoints[touchpoints.length - 1].adId] = (credits[touchpoints[touchpoints.length - 1].adId] ?? 0) + lastShare;
          touchpoints.slice(1, -1).forEach(tp => {
            credits[tp.adId] = (credits[tp.adId] ?? 0) + middleShare;
          });
        }
        break;
      }

      default:
        // data_driven: return equal shares as fallback
        touchpoints.forEach(tp => {
          credits[tp.adId] = (credits[tp.adId] ?? 0) + conversionRevenue / touchpoints.length;
        });
    }

    return credits;
  } catch { return {}; }
}

// ─── Video tracking ───────────────────────────────────────────────────────────

export function createVideoTracker(
  ad: Ad,
  placement: AdPlacement,
  layout: AdLayout
): {
  onStart: () => void;
  onProgress: (pct: number) => void;
  onPause: () => void;
  onResume: () => void;
  onMute: () => void;
  onUnmute: () => void;
  onComplete: () => void;
  onSkip: () => void;
} {
  let quarter25Fired = false;
  let quarter50Fired = false;
  let quarter75Fired = false;
  let completeFired = false;

  return {
    onStart: () => track("video_start", ad, placement, layout),
    onProgress: (pct: number) => {
      if (pct >= 0.25 && !quarter25Fired) { quarter25Fired = true; track("video_q1", ad, placement, layout, { videoProgress: pct }); }
      if (pct >= 0.5 && !quarter50Fired) { quarter50Fired = true; track("video_q2", ad, placement, layout, { videoProgress: pct }); }
      if (pct >= 0.75 && !quarter75Fired) { quarter75Fired = true; track("video_q3", ad, placement, layout, { videoProgress: pct }); }
      if (pct >= 1.0 && !completeFired) { completeFired = true; track("video_complete", ad, placement, layout, { videoProgress: 1 }); }
    },
    onPause: () => track("video_pause", ad, placement, layout),
    onResume: () => track("video_resume", ad, placement, layout),
    onMute: () => track("video_mute", ad, placement, layout),
    onUnmute: () => track("video_unmute", ad, placement, layout),
    onComplete: () => { if (!completeFired) { completeFired = true; track("video_complete", ad, placement, layout, { videoProgress: 1 }); } },
    onSkip: () => track("skip", ad, placement, layout),
  };
}
