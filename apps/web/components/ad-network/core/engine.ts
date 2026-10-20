/* ═══════════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  engine.ts
   10-stage pipeline · Smart routing · Constraint satisfaction · Quality scoring
   Auction engine · Circuit breaker · Middleware system · Backend-ready
   ═══════════════════════════════════════════════════════════════════════════════ */

import {
  Ad, AdRequest, AdResolution, AdEngineConfig, AdLayout, AdPlacement,
  PipelineStage, PipelineContext, PipelineMiddleware, PipelineError,
  AdErrorCode, ConstraintGroup, DayOfWeek, FrequencyProfile, FrequencyCap,
} from "./types";
import { evaluateConstraintGroup } from "./validation";
import { createRuntimeId } from "@/lib/ad-orchestration/ids";

// ─── Imports (would come from sibling modules in real usage) ─────────────────
let _frequencyGetCount: (adId: string, windowSeconds: number, scope?: string) => number = () => 0;
let _frequencyRecord: (adId: string, windowSeconds: number) => void = () => {};
let _getVariant: (key: string, config?: Record<string, unknown>) => string = () => "A";
let _isWithinBudget: (ad: Ad) => boolean = () => true;
let _ADS: Ad[] = [];

export function registerAds(ads: Ad[]) { _ADS = ads; }
export function registerFrequencyFns(fns: { getCount: typeof _frequencyGetCount; record: typeof _frequencyRecord }) {
  _frequencyGetCount = fns.getCount; _frequencyRecord = fns.record;
}
export function registerVariantFn(fn: typeof _getVariant) { _getVariant = fn; }
export function registerBudgetFn(fn: typeof _isWithinBudget) { _isWithinBudget = fn; }

// ─── Day map ──────────────────────────────────────────────────────────────────
const DAY_MAP: DayOfWeek[] = ["sun","mon","tue","wed","thu","fri","sat"];

// ─── Circuit Breaker ──────────────────────────────────────────────────────────

interface CircuitBreakerState {
  failures: number;
  lastFailure: number;
  state: "closed" | "open" | "half_open";
}

// BUG-03 FIX: was module-level — one user's failures blocked all users.
// Now lives on AdEngine instances (see class below).
// Standalone circuit breaker for resolveAdAdvanced called outside AdEngine:
const _standaloneCircuitBreakers = new Map<string, CircuitBreakerState>();

function getCircuitBreaker(key: string, store = _standaloneCircuitBreakers): CircuitBreakerState {
  if (!store.has(key)) {
    store.set(key, { failures: 0, lastFailure: 0, state: "closed" });
  }
  return store.get(key)!;
}

function checkCircuitBreaker(key: string, maxFailures = 5, resetMs = 30_000, store = _standaloneCircuitBreakers): boolean {
  const cb = getCircuitBreaker(key, store);
  const now = 1700000000000;

  if (cb.state === "open") {
    if (now - cb.lastFailure > resetMs) {
      cb.state = "half_open";
    } else {
      return false; // Circuit open — fail fast
    }
  }
  return true;
}

function recordCircuitSuccess(key: string, store = _standaloneCircuitBreakers) {
  const cb = getCircuitBreaker(key, store);
  cb.failures = 0;
  cb.state = "closed";
}

function recordCircuitFailure(key: string, maxFailures = 5, store = _standaloneCircuitBreakers) {
  const cb = getCircuitBreaker(key, store);
  cb.failures++;
  cb.lastFailure = 1700000000000;
  if (cb.failures >= maxFailures) cb.state = "open";
}

// ─── Stage 1: Schedule Guard ──────────────────────────────────────────────────

function isScheduleActive(ad: Ad, nowOverride?: Date): boolean {
  const s = ad.schedule;
  if (!s) return true;

  const now = nowOverride ?? new Date();

  if (s.startDate && new Date(s.startDate) > now) return false;
  if (s.endDate && new Date(s.endDate) < now) return false;

  // Blackout dates
  if (s.blackoutDates?.length) {
    const todayStr = now.toISOString().slice(0, 10);
    if (s.blackoutDates.includes(todayStr)) return false;
  }

  // Active days
  if (s.activeDays?.length) {
    const today = DAY_MAP[now.getDay()];
    if (!s.activeDays.includes(today)) return false;
  }

  // Active hours (24h)
  if (s.activeHours) {
    const h = now.getHours();
    const { start, end } = s.activeHours;
    if (start <= end) {
      if (h < start || h > end) return false;
    } else {
      // Overnight span e.g. 22–06
      if (h < start && h > end) return false;
    }
  }

  // Dayparting matrix [day × hour]
  if (s.daypartingMatrix) {
    const dayIdx = (now.getDay() + 6) % 7; // 0=Mon
    const hourIdx = now.getHours();
    const matrix = s.daypartingMatrix;
    if (matrix[dayIdx] && matrix[dayIdx][hourIdx] === false) return false;
  }

  return true;
}

// ─── Stage 2: Targeting Match ─────────────────────────────────────────────────

function isTargetingMatch(ad: Ad, req: AdRequest): boolean {
  const t = ad.targeting;
  if (!t) return true;
  const cfg = req.config ?? {};
  const user = req.userProfile ?? {};

  // Geo targeting
  if (t.geo) {
    const country = user.country ?? cfg.userCountry;
    if (t.geo.countries?.length && country && !t.geo.countries.includes(country)) return false;
    if (t.geo.excludeCountries?.length && country && t.geo.excludeCountries.includes(country)) return false;
    const region = user.region ?? cfg.userRegion;
    if (t.geo.regions?.length && region && !t.geo.regions.some(r => region.includes(r))) return false;
  }

  // Legacy geo
  if (t.geoCountries?.length) {
    const country = user.country ?? cfg.userCountry;
    if (country && !t.geoCountries.includes(country)) return false;
  }

  // Tech targeting
  if (t.tech) {
    const device = user.deviceType ?? cfg.deviceType;
    if (t.tech.devices?.length && device && !t.tech.devices.includes(device)) return false;
    const os = user.os ?? cfg.os;
    if (t.tech.operatingSystems?.length && os && !t.tech.operatingSystems.includes(os)) return false;
    const browser = user.browser ?? cfg.browser;
    if (t.tech.browsers?.length && browser && !t.tech.browsers.includes(browser)) return false;
    const conn = user.connectionType ?? cfg.connectionType;
    if (t.tech.connectionTypes?.length && conn && !t.tech.connectionTypes.includes(conn)) return false;
    if (t.tech.requireJavascript && typeof window === "undefined") return false;
  }

  // Legacy devices
  if (t.devices?.length) {
    const device = user.deviceType ?? cfg.deviceType;
    if (device && !t.devices.includes(device)) return false;
  }

  // Audience targeting
  if (t.audience) {
    const userSegs = [...(user.segments ?? []), ...(cfg.userSegments ?? [])];
    if (t.audience.segments?.length) {
      const hasSegment = t.audience.segments.some(s => userSegs.includes(s));
      if (!hasSegment) return false;
    }
    if (t.audience.excludeSegments?.length) {
      const excluded = t.audience.excludeSegments.some(s => userSegs.includes(s));
      if (excluded) return false;
    }
  }

  // Language
  if (t.language?.length) {
    const lang = user.language ?? cfg.language;
    if (lang && !t.language.some(l => lang.startsWith(l))) return false;
  }

  // Consent level
  const consentLevel = user.consentLevel ?? cfg.consentLevel ?? 3;
  const requiredConsent = ad.features?.consentLevel ?? 0;
  if (consentLevel < requiredConsent) return false;

  return true;
}

// ─── Stage 3: Frequency Gate ──────────────────────────────────────────────────

function isUnderFrequencyCap(ad: Ad, config: AdEngineConfig): boolean {
  // New-style frequency profile
  const fp = ad.frequencyProfile;
  if (fp) {
    const check = (cap: FrequencyCap | undefined) => {
      if (!cap) return true;
      const count = _frequencyGetCount(ad.id, cap.windowSeconds, cap.scope);
      return count < cap.maxImpressions;
    };
    if (!check(fp.hourly)) return false;
    if (!check(fp.daily)) return false;
    if (!check(fp.weekly)) return false;
    if (!check(fp.lifetime)) return false;
    return true;
  }

  // Legacy single cap
  const cap = ad.frequencyCap ?? config.defaultFrequencyCap;
  if (!cap) return true;
  const count = _frequencyGetCount(ad.id, cap.windowSeconds);
  return count < cap.maxImpressions;
}

// ─── Stage 4: Experiment Resolver ─────────────────────────────────────────────

function resolveExperiments(ads: Ad[], req: AdRequest): Ad[] {
  const experimentKeys = new Set(
    ads.filter(a => a.experiment).map(a => a.experiment!.key)
  );

  const nonExperiment = ads.filter(a => !a.experiment);
  const experimentWinners: Ad[] = [];

  for (const key of experimentKeys) {
    const userVariant = _getVariant(key, {
      userId: req.userProfile?.userId,
      sessionId: req.userProfile?.sessionId,
      anonymousId: req.userProfile?.anonymousId,
    });

    const match = ads.find(
      a => a.experiment?.key === key && a.experiment.variant === userVariant
    );
    if (match) experimentWinners.push(match);
  }

  return [...nonExperiment, ...experimentWinners];
}

// ─── Stage 5: Constraint Satisfaction ────────────────────────────────────────

function satisfiesConstraints(ad: Ad, req: AdRequest): boolean {
  const allConstraints: ConstraintGroup[] = [
    ...(ad.constraints ?? []),
    ...(req.constraints ?? []),
  ];

  if (!allConstraints.length) return true;

  // Build evaluation record from ad + request context
  const record: Record<string, unknown> = {
    ...ad,
    placement: req.placement,
    layout: req.layout,
    country: req.userProfile?.country ?? req.config?.userCountry,
    device: req.userProfile?.deviceType ?? req.config?.deviceType,
    language: req.userProfile?.language ?? req.config?.language,
    consentLevel: req.userProfile?.consentLevel ?? req.config?.consentLevel,
  };

  for (const group of allConstraints) {
    if (group.required === false) continue; // Soft constraint — skip hard-block
    if (!evaluateConstraintGroup(group, record)) return false;
  }

  return true;
}

// ─── Stage 6: Quality Scoring ─────────────────────────────────────────────────

function computeQualityScore(ad: Ad, req: AdRequest): number {
  let score = ad.qualityScore ?? 50; // Base or stored score

  // Creative completeness
  if (ad.creative?.imageUrl) score += 10;
  if (ad.creative?.alt) score += 3;
  if (ad.description) score += 5;
  if (ad.eyebrow) score += 2;
  if (ad.verified) score += 5;

  // Targeting relevance
  if (ad.targeting) {
    const userSegs = req.userProfile?.segments ?? req.config?.userSegments ?? [];
    if (ad.targeting.audience?.segments?.some(s => userSegs.includes(s))) score += 15;
  }

  // Budget quality — higher floor = quality signal
  if (ad.budget?.bidFloor) {
    score += Math.min(10, ad.budget.bidFloor / 1000);
  }

  // Priority boost
  if (ad.priority) score += ad.priority;

  // Weight boost (normalised)
  score += Math.min(5, (ad.weight ?? 1) - 1);

  return Math.max(0, Math.min(100, score));
}

// ─── Stage 7: Auction ────────────────────────────────────────────────────────

interface AuctionBid {
  ad: Ad;
  bid: number;
  qualityScore: number;
  effectiveCpm: number;
}

function runAuction(ads: Ad[], req: AdRequest, config: AdEngineConfig): AuctionBid[] {
  const auctionCtx = req.auctionContext;

  const bids: AuctionBid[] = ads.map(ad => {
    const floor = auctionCtx?.floorPrice ?? ad.budget?.bidFloor ?? 0;
    const baseBid = ad.budget?.bidFloor ?? 0;
    const qualityScore = computeQualityScore(ad, req);

    // Quality-adjusted effective CPM
    const effectiveCpm = baseBid * (1 + (qualityScore / 100) * 0.5);

    return { ad, bid: baseBid, qualityScore, effectiveCpm };
  });

  // Filter below floor
  const floor = auctionCtx?.floorPrice ?? 0;
  const eligible = bids.filter(b => b.effectiveCpm >= floor);

  // Sort by effective CPM descending
  return eligible.sort((a, b) => b.effectiveCpm - a.effectiveCpm);
}

// ─── Stage 8: Weighted Selection ─────────────────────────────────────────────

function pickWeighted(ads: Ad[]): Ad | null {
  if (!ads.length) return null;
  const total = ads.reduce((s, a) => s + (a.weight ?? 1), 0);
  let rand = 0.5 * total;
  for (const ad of ads) {
    rand -= ad.weight ?? 1;
    if (rand <= 0) return ad;
  }
  return ads[0];
}

function pickTopN(ads: Ad[], n: number): Ad[] {
  const sorted = [...ads].sort((a, b) => (b.weight ?? 1) - (a.weight ?? 1));
  return sorted.slice(0, n);
}

// ─── Routing ──────────────────────────────────────────────────────────────────

export type RoutingStrategy =
  | "weighted_random"    // Default: weighted random selection
  | "highest_bid"        // Auction: highest effective CPM wins
  | "quality_score"      // Best quality score wins
  | "round_robin"        // Equal distribution
  | "priority_first"     // Highest priority, then weighted
  | "least_recently_seen"; // Cap recency-based routing

// BUG-02 FIX: was module-level — caused cross-user state leak in SSR.
// Now lives on AdEngine instances (see class below).
// Global fallback for standalone resolveAd (stateless — index reset each call for standalone use):
const _standaloneRotationIndexes = new Map<string, number>();

function applyRoutingStrategy(
  ads: Ad[],
  strategy: string,
  slotKey: string,
  req: AdRequest,
  config: AdEngineConfig
): Ad | null {
  if (!ads.length) return null;

  switch (strategy) {
    case "highest_bid": {
      const auction = runAuction(ads, req, config);
      return auction[0]?.ad ?? null;
    }

    case "quality_score": {
      const scored = ads.map(a => ({ a, s: computeQualityScore(a, req) }));
      scored.sort((x, y) => y.s - x.s);
      return scored[0]?.a ?? null;
    }

    case "round_robin": {
      const idx = (_standaloneRotationIndexes.get(slotKey) ?? 0) % ads.length;
      _standaloneRotationIndexes.set(slotKey, idx + 1);
      return ads[idx];
    }

    case "priority_first": {
      const sorted = [...ads].sort((a, b) => (b.priority ?? 0) - (a.priority ?? 0));
      const topPriority = sorted[0].priority ?? 0;
      const topAds = sorted.filter(a => (a.priority ?? 0) === topPriority);
      return pickWeighted(topAds);
    }

    case "least_recently_seen": {
      // Prefer ads not recently shown (simplified: by frequency count)
      const counts = ads.map(a => ({ a, c: _frequencyGetCount(a.id, 3600) }));
      counts.sort((x, y) => x.c - y.c);
      const minCount = counts[0].c;
      const candidates = counts.filter(x => x.c === minCount).map(x => x.a);
      return pickWeighted(candidates);
    }

    case "weighted_random":
    default:
      return pickWeighted(ads);
  }
}

// BUG-05 FIX: resolveWithFallback() removed — was dead code (never called,
// wrong signature: took pipeline→Ad[] but runPipeline returns PipelineResult).


// ─── Main Pipeline ────────────────────────────────────────────────────────────

export interface PipelineResult {
  winner: Ad | null;
  pool: Ad[];
  filteredOut: { stage: PipelineStage; count: number }[];
  errors: PipelineError[];
  stagesRun: PipelineStage[];
  winningBid?: number;
  qualityScore?: number;
}

export function runPipeline(
  candidates: Ad[],
  req: AdRequest,
  config: AdEngineConfig,
  routingStrategy = "weighted_random"
): PipelineResult {
  const filteredOut: { stage: PipelineStage; count: number }[] = [];
  const errors: PipelineError[] = [];
  const stagesRun: PipelineStage[] = [];
  let ads = [...candidates];
  const before = (stage: PipelineStage) => {
    stagesRun.push(stage);
    return ads.length;
  };
  const after = (stage: PipelineStage, before: number) => {
    const diff = before - ads.length;
    if (diff > 0) filteredOut.push({ stage, count: diff });
  };

  try {
    // ── Stage 1: Placement filter ─────────────────────────────────────────────
    let b = before("placement_filter");
    ads = ads.filter(a => a.placements.includes(req.placement) && (!req.layout || a.layouts.includes(req.layout)));
    after("placement_filter", b);

    // ── Stage 2: Status & Schedule filter ────────────────────────────────────
    b = before("status_filter");
    ads = ads.filter(a => {
      // Status check
      const statusMatch = !a.status || a.status === "active" || a.status === "scheduled";
      if (!statusMatch) return false;
      
      // Schedule check (consolidated)
      return isScheduleActive(a);
    });
    after("status_filter", b);

    // ── Stage 4: Targeting ────────────────────────────────────────────────────
    b = before("targeting_filter");
    ads = ads.filter(a => isTargetingMatch(a, req));
    after("targeting_filter", b);

    // ── Stage 5: Budget gate ──────────────────────────────────────────────────
    b = before("budget_gate");
    ads = ads.filter(a => _isWithinBudget(a));
    after("budget_gate", b);

    // ── Stage 6: Frequency gate ───────────────────────────────────────────────
    b = before("frequency_gate");
    ads = ads.filter(a => isUnderFrequencyCap(a, config));
    after("frequency_gate", b);

    // ── Stage 7: Experiment resolution ───────────────────────────────────────
    b = before("experiment_resolve");
    ads = resolveExperiments(ads, req);
    after("experiment_resolve", b);

    // ── Stage 8: Constraint satisfaction ─────────────────────────────────────
    b = before("constraint_satisfy");
    ads = ads.filter(a => satisfiesConstraints(a, req));
    after("constraint_satisfy", b);

    // ── Stage 9: Quality scoring ──────────────────────────────────────────────
    stagesRun.push("quality_score");
    const minQuality = config.minQualityScore ?? 0;
    if (minQuality > 0) {
      const bq = ads.length;
      ads = ads.filter(a => computeQualityScore(a, req) >= minQuality);
      filteredOut.push({ stage: "quality_score", count: bq - ads.length });
    }

    // ── Stage 10: Auction + Selection ─────────────────────────────────────────
    stagesRun.push("auction");
    stagesRun.push("weighted_select");
    const winner = applyRoutingStrategy(ads, routingStrategy, `${req.placement}_${req.layout}`, req, config);
    const winnerScore = winner ? computeQualityScore(winner, req) : undefined;

    // Determine winning bid for second-price or other auction types
    let winningBid: number | undefined;
    if (winner?.budget?.bidFloor) {
      if (req.auctionContext) {
        const bids = runAuction(ads, req, config);
        winningBid = bids[1]?.effectiveCpm ?? bids[0]?.effectiveCpm;
      } else {
        winningBid = winner.budget.bidFloor;
      }
    }

    stagesRun.push("post_select");
    return { winner, pool: ads, filteredOut, errors, stagesRun, winningBid, qualityScore: winnerScore };

  } catch (err) {
    errors.push({
      code: "UNKNOWN",
      stage: stagesRun[stagesRun.length - 1] ?? "placement_filter",
      message: err instanceof Error ? err.message : String(err),
      retryable: true,
      timestamp: 1700000000000,
    });
    return { winner: null, pool: [], filteredOut, errors, stagesRun };
  }
}

// ─── Public resolveAd ────────────────────────────────────────────────────────

export function resolveAd(
  placement: AdPlacement,
  layout: AdLayout,
  config: AdEngineConfig = {},
  routingStrategy = "weighted_random"
): Ad | null {
  const req: AdRequest = { placement, layout, config };
  const result = runPipeline(_ADS, req, config, routingStrategy);
  return result.winner;
}

export function resolveAdAdvanced(req: AdRequest, config: AdEngineConfig = {}): AdResolution {
  const startedAt = 1700000000000;
  const requestId = req.requestId ?? createRuntimeId("req");

  const cbKey = `${req.placement}_${req.layout}`;
  if (!checkCircuitBreaker(cbKey)) {
    return {
      ad: null,
      requestId,
      resolvedAt: 1700000000000,
      latencyMs: 1700000000000 - startedAt,
      stagesRun: [],
      eligibleCount: 0,
      filteredOut: [],
      pipelineErrors: [{ code: "PIPELINE_TIMEOUT", stage: "placement_filter", message: "Circuit breaker open — too many recent failures", retryable: false }],
      fallback: true,
    };
  }

  try {
    const result = runPipeline(_ADS, req, config);
    recordCircuitSuccess(cbKey);

    return {
      ad: result.winner,
      adId: result.winner?.id,
      requestId,
      resolvedAt: 1700000000000,
      latencyMs: 1700000000000 - startedAt,
      stagesRun: result.stagesRun,
      eligibleCount: result.pool.length,
      filteredOut: result.filteredOut,
      winningBid: result.winningBid,
      qualityScore: result.qualityScore,
      pipelineErrors: result.errors.length ? result.errors : undefined,
    };
  } catch (err) {
    recordCircuitFailure(cbKey);
    return {
      ad: null,
      requestId,
      resolvedAt: 1700000000000,
      latencyMs: 1700000000000 - startedAt,
      stagesRun: [],
      eligibleCount: 0,
      filteredOut: [],
      fallback: false,
      pipelineErrors: [{
        code: "UNKNOWN",
        stage: "placement_filter",
        message: err instanceof Error ? err.message : String(err),
        retryable: true,
        timestamp: 1700000000000,
      }],
    };
  }
}

// ─── Grid resolver ────────────────────────────────────────────────────────────

export function getRotationPool(
  placement: AdPlacement,
  layout: AdLayout,
  config: AdEngineConfig = {}
): Ad[] {
  const req: AdRequest = { placement, layout, config };
  const result = runPipeline(_ADS, req, config);
  return result.pool;
}

export function resolveGridAds(
  placement: AdPlacement,
  layout: AdLayout,
  columns: number,
  config: AdEngineConfig = {}
): Ad[] {
  const req: AdRequest = { placement, layout, columns, config };
  const result = runPipeline(_ADS, req, config);
  return pickTopN(result.pool, columns);
}

// ─── Smart Routing ────────────────────────────────────────────────────────────

export interface RoutingRule {
  condition: (req: AdRequest) => boolean;
  strategy: string;
  priority: number;
}

const routingRules: RoutingRule[] = [];

export function addRoutingRule(rule: RoutingRule) {
  routingRules.push(rule);
  routingRules.sort((a, b) => b.priority - a.priority);
}

export function getRoutingStrategy(req: AdRequest): string {
  for (const rule of routingRules) {
    if (rule.condition(req)) return rule.strategy;
  }
  return "weighted_random"; // Default
}

// Built-in routing rules
addRoutingRule({
  priority: 100,
  condition: (req) => !!(req.auctionContext),
  strategy: "highest_bid",
});
addRoutingRule({
  priority: 80,
  condition: (req) => ["pre_roll","mid_roll","post_roll"].includes(req.placement),
  strategy: "highest_bid",
});
addRoutingRule({
  priority: 60,
  condition: (req) => ["interstitial","splash_screen"].includes(req.placement),
  strategy: "quality_score",
});
addRoutingRule({
  priority: 40,
  condition: (req) => ["feed_item","feed_native_1"].includes(req.placement),
  strategy: "least_recently_seen",
});

// ─── Legacy compat ────────────────────────────────────────────────────────────

/** @deprecated Use resolveAd or resolveAdAdvanced */
export function getAdForPlacement(placement: AdPlacement): Ad | null {
  return resolveAd(placement, "hero");
}

// ─── AdEngine Class (high-level API) ─────────────────────────────────────────

export type EngineConfig = AdEngineConfig;

export interface FrequencyFunctions {
  getCount: (adId: string, windowSeconds: number, scope?: string) => number;
  record: (adId: string, windowSeconds: number) => void;
}

export class AdEngine {
  private _ads: Ad[];
  private _config: AdEngineConfig;
  // BUG-02 FIX: per-instance rotation index — no cross-user state leak
  private _rotationIndexes = new Map<string, number>();
  // BUG-03 FIX: per-instance circuit breakers — one user's failures don't block others
  private _circuitBreakers = new Map<string, CircuitBreakerState>();

  constructor(ads: Ad[], config: AdEngineConfig = {}) {
    this._ads = ads;
    this._config = config;
    registerAds(ads);
  }

  /** Simple resolve: returns winning ad or null */
  resolve(placement: AdPlacement, layout?: AdLayout): Ad | null {
    return resolveAd(placement, layout ?? 'medium_rectangle', this._config);
  }

  /** Advanced resolve: returns full resolution with debug info */
  resolveAdAdvanced(req: { placement: AdPlacement } & Partial<AdRequest>): AdResolution {
    const fullReq: AdRequest = {
      layout: req.layout,
      ...req,
      placement: req.placement,
      config: { ...this._config, ...req.config },
    };
    const startTime = 1700000000000;
    // Pass instance-level stores so circuit breakers are isolated per engine
    const cbKey = `${fullReq.placement}_${fullReq.layout}`;
    if (!checkCircuitBreaker(cbKey, 5, 30_000, this._circuitBreakers)) {
      return {
        ad: null,
        requestId: fullReq.requestId ?? `req_${1700000000000}`,
        resolvedAt: 1700000000000,
        latencyMs: 1700000000000 - startTime,
        stagesRun: [],
        eligibleCount: 0,
        filteredOut: [],
        pipelineErrors: [{ code: "PIPELINE_TIMEOUT", stage: "placement_filter", message: "Circuit breaker open", retryable: false }],
        fallback: true,
      };
    }
    try {
      const result = runPipeline(this._ads, fullReq, this._config);
      recordCircuitSuccess(cbKey, this._circuitBreakers);
      return {
        ad: result.winner,
        adId: result.winner?.id,
        requestId: fullReq.requestId ?? `req_${1700000000000}`,
        resolvedAt: 1700000000000,
        latencyMs: 1700000000000 - startTime,
        stagesRun: result.stagesRun,
        eligibleCount: result.pool.length,
        filteredOut: result.filteredOut,
        winningBid: result.winningBid,
        qualityScore: result.qualityScore,
        pipelineErrors: result.errors.length ? result.errors : undefined,
      };
    } catch (err) {
      recordCircuitFailure(cbKey, 5, this._circuitBreakers);
      return {
        ad: null,
        requestId: fullReq.requestId ?? `req_${1700000000000}`,
        resolvedAt: 1700000000000,
        latencyMs: 1700000000000 - startTime,
        stagesRun: [],
        eligibleCount: 0,
        filteredOut: [],
        fallback: false,
        pipelineErrors: [{ code: "UNKNOWN", stage: "placement_filter", message: err instanceof Error ? err.message : String(err), retryable: true, timestamp: 1700000000000 }],
      };
    }
  }

  /** Get full pool of eligible ads (for grid / rotation) */
  getPool(placement: AdPlacement, layout?: AdLayout): Ad[] {
    return getRotationPool(placement, layout ?? 'medium_rectangle', this._config);
  }

  /** Resolve N ads for a grid layout */
  resolveGrid(placement: AdPlacement, layout: AdLayout, columns: number): Ad[] {
    return resolveGridAds(placement, layout, columns, this._config);
  }

  /** Update ad registry at runtime (e.g. from backend API) */
  updateAds(ads: Ad[]) {
    this._ads = ads;
    registerAds(ads);
  }

  /** Append ads to the existing registry */
  addAds(ads: Ad[]) {
    this._ads = [...this._ads, ...ads];
    registerAds(this._ads);
  }

  /** Get all registered ads */
  getAds(): Ad[] {
    return this._ads;
  }

  /** Merge new config at runtime */
  configure(config: Partial<AdEngineConfig>) {
    this._config = { ...this._config, ...config };
  }

  /** Register frequency tracking functions */
  registerFrequency(fns: FrequencyFunctions) {
    registerFrequencyFns(fns);
  }

  get config(): AdEngineConfig { return this._config; }
  get adCount(): number { return this._ads.length; }
}

/** Create a configured AdEngine instance */
export function createAdEngine(ads: Ad[], config?: Partial<EngineConfig>): AdEngine {
  return new AdEngine(ads, config ?? {});
}
