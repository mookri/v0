/* ═══════════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  pricing.ts
   50+ pricing models · Dynamic floors · Bid shading · Pacing algorithms
   Budget tracking · Revenue attribution · eCPM optimization
   ═══════════════════════════════════════════════════════════════════════════════ */

import { Ad, AdEventType, PricingModel, PricingConfig, PricingTier } from "./types";

// ─── Revenue calculation per event ───────────────────────────────────────────

/**
 * Map of (PricingModel × triggering AdEventType) → true/false
 * Only listed events generate revenue for that model.
 */
export const BILLING_EVENTS: Partial<Record<PricingModel, AdEventType[]>> = {
  CPM:          ["impression"],
  CPC:          ["click"],
  CPV:          ["video_complete", "viewable"],
  CPA:          ["conversion"],
  CPL:          ["form_submit", "conversion"],
  CPI:          ["conversion"],         // App install tracked as conversion
  CPE:          ["hover", "expand", "video_start", "scroll_to", "form_start"],
  CPD:          ["impression"],         // Day-based, handled by pacing
  CPT:          ["impression"],
  flat_rate:    ["impression"],
  vCPM:         ["viewable"],
  CPCV:         ["video_complete"],
  CPF:          ["viewable"],           // Above fold viewable
  AVOC:         ["video_complete"],     // Audible + viewable on complete
  ROAS:         ["conversion"],
  CPS:          ["conversion"],
  CPO:          ["conversion"],
  CPR:          ["form_submit", "conversion"],
  CPTM:         ["impression"],
  CPQ:          ["conversion"],
  CPC_enhanced: ["click", "conversion"],
  smart_cpa:    ["conversion"],
  target_roas:  ["conversion"],
  max_conversions: ["conversion"],
  first_price_auction: ["impression"],
  second_price_auction: ["impression"],
  header_bidding_cpm: ["impression"],
  open_rtb_cpm: ["impression"],
  private_deal_cpm: ["impression"],
  preferred_deal_cpm: ["impression"],
  programmatic_guaranteed_cpm: ["impression"],
  dynamic_floor: ["impression"],
  yield_optimised: ["impression", "viewable"],
  revenue_share: ["conversion", "click"],
  profit_share:  ["conversion"],
  gross_margin_share: ["conversion"],
  CPH:           ["impression"],        // Prorated per display second
  tenancy_day:   ["impression"],
  tenancy_week:  ["impression"],
  tenancy_month: ["impression"],
  burst_rate:    ["impression"],
  hybrid_cpm_cpc: ["impression", "click"],
  hybrid_cpa_cpv: ["video_complete", "conversion"],
  stepped_cpm:   ["impression"],
  tiered_cpc:    ["click"],
  bid_shading:   ["impression"],
  subscription_monthly: ["impression"],
  subscription_annual:  ["impression"],
  credit_bundle:  ["impression", "click", "viewable"],
  prepaid_credits: ["impression", "click", "viewable", "conversion"],
  pay_for_results: ["conversion"],
  cost_per_attention: ["viewable"],
  cost_per_memory:    ["viewable"],     // Post-campaign, tracked separately
  guaranteed_outcomes: ["conversion"],
};

/**
 * Calculate revenue credit for a single event.
 * Returns 0 if this event type does not trigger billing for the model.
 */
export function calculateRevenue(
  ad: Ad,
  eventType: AdEventType,
  context?: {
    viewabilityRatio?: number;
    videoProgress?: number;
    windowMs?: number;
    qualityScore?: number;
    generatedRevenue?: number;   // For ROAS / revenue_share
  }
): number {
  const budget = ad.budget;
  if (!budget) return 0;

  const { model, bidFloor, revenueSharePct, targetKpi, bidShadeAlpha, tiers } = budget;

  const billingEvents = BILLING_EVENTS[model];
  if (!billingEvents?.includes(eventType)) return 0;

  // Apply viewability requirement for vCPM
  if (model === "vCPM" && context?.viewabilityRatio !== undefined) {
    const minView = budget.viewabilityMinPct ?? 0.5;
    if (context.viewabilityRatio < minView) return 0;
  }

  // Apply completion requirement for CPCV / AVOC
  if ((model === "CPCV" || model === "AVOC") && context?.videoProgress !== undefined) {
    const minComp = budget.completionMinPct ?? 1.0;
    if (context.videoProgress < minComp) return 0;
  }

  // Base revenue computation
  let revenue = 0;

  switch (model) {
    case "CPM":
    case "vCPM":
    case "CPF":
    case "CPT":
    case "CPTM":
    case "header_bidding_cpm":
    case "open_rtb_cpm":
    case "private_deal_cpm":
    case "preferred_deal_cpm":
    case "programmatic_guaranteed_cpm":
    case "dynamic_floor":
    case "tenancy_day":
    case "tenancy_week":
    case "tenancy_month":
    case "subscription_monthly":
    case "subscription_annual":
    case "burst_rate":
      revenue = bidFloor / 1000;   // Per impression = bid / 1000
      break;

    case "CPC":
    case "tiered_cpc":
    case "CPC_enhanced":
      revenue = bidFloor;
      break;

    case "CPV":
    case "CPCV":
    case "AVOC":
    case "cost_per_attention":
    case "cost_per_memory":
      revenue = bidFloor;
      break;

    case "CPA":
    case "smart_cpa":
    case "CPL":
    case "CPI":
    case "CPS":
    case "CPO":
    case "CPR":
    case "CPQ":
    case "pay_for_results":
    case "guaranteed_outcomes":
      revenue = bidFloor;
      break;

    case "CPE":
      revenue = bidFloor;
      break;

    case "flat_rate":
      // Spread flat rate across expected impressions (100,000 default)
      revenue = bidFloor / 100_000;
      break;

    case "ROAS":
    case "target_roas":
    case "max_conversions": {
      // ROAS: revenue = generated_revenue × targetKpi (ROAS fraction)
      const gen = context?.generatedRevenue ?? 0;
      const fraction = targetKpi ?? 0.3; // 30% of generated revenue
      revenue = gen * fraction;
      break;
    }

    case "revenue_share":
    case "profit_share":
    case "gross_margin_share": {
      const gen = context?.generatedRevenue ?? 0;
      revenue = gen * (revenueSharePct ?? 0.2);
      break;
    }

    case "CPH": {
      // Per-hour display: prorated by windowMs / 3600000
      const hrs = (context?.windowMs ?? 0) / 3_600_000;
      revenue = bidFloor * hrs;
      break;
    }

    case "second_price_auction": {
      // Caller must pass winning bid; here we return bidFloor as proxy
      revenue = bidFloor / 1000;
      break;
    }

    case "bid_shading": {
      // Bid shading: effective bid = floor + alpha × (bid - floor)
      const alpha = bidShadeAlpha ?? 0.5;
      const shadedBid = bidFloor * (1 + alpha * 0.5); // simplified
      revenue = shadedBid / 1000;
      break;
    }

    case "hybrid_cpm_cpc": {
      if (eventType === "impression") revenue = bidFloor / 1000 * 0.5;
      else if (eventType === "click") revenue = bidFloor * 0.5;
      break;
    }

    case "hybrid_cpa_cpv": {
      if (eventType === "video_complete") revenue = bidFloor * 0.4;
      else if (eventType === "conversion") revenue = bidFloor * 0.6;
      break;
    }

    case "stepped_cpm": {
      // Apply volume discount tiers
      revenue = bidFloor / 1000;
      break;
    }

    case "yield_optimised": {
      // Quality-adjusted CPM
      const qs = (context?.qualityScore ?? 50) / 100;
      revenue = (bidFloor / 1000) * (1 + qs * 0.3);
      break;
    }

    case "credit_bundle":
    case "prepaid_credits":
      revenue = bidFloor;   // Deduct from pre-purchased credits
      break;

    default:
      revenue = bidFloor;
  }

  // Apply volume tier adjustment
  if (tiers?.length) {
    revenue = applyTierPricing(revenue, tiers, getImpressionCount(ad.id));
  }

  return Math.max(0, revenue);
}

function getImpressionCount(adId: string): number {
  // BUG-18 FIX: explicit SSR guard — localStorage does not exist on the server
  if (typeof window === "undefined" || typeof localStorage === "undefined") return 0;
  try {
    const raw = localStorage.getItem(`ad_imp_total_${adId}`);
    return raw ? parseInt(raw, 10) : 0;
  } catch { return 0; }
}

export function applyTierPricing(baseRevenue: number, tiers: PricingTier[], impressionCount: number): number {
  // Find applicable tier (highest minVolume not exceeding current count)
  let activeTier: PricingTier | undefined;
  for (const tier of tiers) {
    if (impressionCount >= tier.minVolume) activeTier = tier;
  }
  if (!activeTier) return baseRevenue;
  return activeTier.unitCost / 1000; // Unit cost is per-impression pricing
}

// ─── Pacing Algorithms ────────────────────────────────────────────────────────

export type PacingAlgorithm = "even" | "frontloaded" | "backloaded" | "goal_based";

export interface PacingState {
  adId: string;
  totalBudget: number;
  dailyCap: number;
  totalSpent: number;
  dailySpent: number;
  lastResetDate: string;
  impressionsByHour: number[];      // 24 slots
  clicksByHour: number[];
  revenueByHour: number[];
  campaignStartDate?: string;
  campaignEndDate?: string;
  targetImpressions?: number;
  actualImpressions: number;
}

const PACING_PREFIX = "ad_pace_v2_";

function today(): string {
  return new Date().toISOString().slice(0, 10);
}

function currentHour(): number {
  return new Date().getHours();
}

function loadPacingState(adId: string): PacingState {
  if (typeof window === "undefined") {
    return { adId, totalBudget: 0, dailyCap: 0, totalSpent: 0, dailySpent: 0, lastResetDate: today(), impressionsByHour: Array(24).fill(0), clicksByHour: Array(24).fill(0), revenueByHour: Array(24).fill(0), actualImpressions: 0 };
  }
  try {
    const raw = localStorage.getItem(`${PACING_PREFIX}${adId}`);
    if (raw) return JSON.parse(raw);
  } catch { /**/ }
  return { adId, totalBudget: 0, dailyCap: 0, totalSpent: 0, dailySpent: 0, lastResetDate: today(), impressionsByHour: Array(24).fill(0), clicksByHour: Array(24).fill(0), revenueByHour: Array(24).fill(0), actualImpressions: 0 };
}

function savePacingState(state: PacingState): void {
  if (typeof window === "undefined") return;
  try {
    localStorage.setItem(`${PACING_PREFIX}${state.adId}`, JSON.stringify(state));
  } catch { /**/ }
}

export function recordSpend(
  ad: Ad,
  eventType: AdEventType,
  context?: Parameters<typeof calculateRevenue>[2]
): { withinBudget: boolean; revenue: number; overdelivery: number } {
  const revenue = calculateRevenue(ad, eventType, context);
  if (revenue === 0 || !ad.budget) return { withinBudget: true, revenue: 0, overdelivery: 0 };

  const state = loadPacingState(ad.id);
  const todayStr = today();
  const hour = currentHour();

  // Reset daily counters if date changed
  if (state.lastResetDate !== todayStr) {
    state.dailySpent = 0;
    state.impressionsByHour = Array(24).fill(0);
    state.clicksByHour = Array(24).fill(0);
    state.revenueByHour = Array(24).fill(0);
    state.lastResetDate = todayStr;
  }

  state.totalSpent += revenue;
  state.dailySpent += revenue;
  state.revenueByHour[hour] = (state.revenueByHour[hour] ?? 0) + revenue;

  if (eventType === "impression") {
    state.actualImpressions++;
    state.impressionsByHour[hour] = (state.impressionsByHour[hour] ?? 0) + 1;
  }
  if (eventType === "click") {
    state.clicksByHour[hour] = (state.clicksByHour[hour] ?? 0) + 1;
  }

  savePacingState(state);

  const { totalBudget, dailyCap, hourlyCap } = ad.budget;

  // Overdelivery tolerance buffer (default 10%)
  const overBuffer = ad.features?.overbookingBuffer ?? 0.1;
  const overdelivery = totalBudget ? Math.max(0, (state.totalSpent / totalBudget) - 1) : 0;

  const withinTotal = totalBudget
    ? state.totalSpent <= totalBudget * (1 + overBuffer) : true;
  const withinDaily = dailyCap
    ? state.dailySpent <= dailyCap * (1 + overBuffer) : true;
  const withinHourly = hourlyCap
    ? (state.revenueByHour[hour] ?? 0) <= hourlyCap * (1 + overBuffer) : true;

  return { withinBudget: withinTotal && withinDaily && withinHourly, revenue, overdelivery };
}

export function isWithinBudget(ad: Ad): boolean {
  if (!ad.budget) return true;

  const state = loadPacingState(ad.id);
  const todayStr = today();
  const hour = currentHour();

  const dailySpent = state.lastResetDate === todayStr ? state.dailySpent : 0;
  const hourlySpent = state.lastResetDate === todayStr
    ? (state.revenueByHour[hour] ?? 0) : 0;

  if (ad.budget.totalBudget && state.totalSpent >= ad.budget.totalBudget) return false;
  if (ad.budget.dailyCap && dailySpent >= ad.budget.dailyCap) return false;
  if (ad.budget.hourlyCap && hourlySpent >= ad.budget.hourlyCap) return false;

  return true;
}

/**
 * Pacing multiplier — how fast should we spend?
 * Returns 0–2: 1 = on pace, <1 = slow down, >1 = speed up
 */
export function getPacingMultiplier(ad: Ad, algorithm: PacingAlgorithm = "even"): number {
  if (!ad.budget?.dailyCap) return 1;

  const state = loadPacingState(ad.id);
  const hour = currentHour();
  const elapsed = (hour + 1) / 24;     // Fraction of day elapsed
  const spentFraction = state.dailySpent / ad.budget.dailyCap;

  switch (algorithm) {
    case "even":
      // On pace if spentFraction ≈ elapsed
      return elapsed > 0 ? spentFraction / elapsed : 1;

    case "frontloaded": {
      // 70% in first half of day
      const targetFraction = hour < 12 ? (hour / 12) * 0.7 : 0.7 + ((hour - 12) / 12) * 0.3;
      return targetFraction > 0 ? spentFraction / targetFraction : 1;
    }

    case "backloaded": {
      const targetFraction = hour < 12 ? (hour / 12) * 0.3 : 0.3 + ((hour - 12) / 12) * 0.7;
      return targetFraction > 0 ? spentFraction / targetFraction : 1;
    }

    case "goal_based": {
      // Maintain target pacing based on historical hourly patterns
      // Simplified: even pacing with smoothing
      return elapsed > 0 ? Math.min(2, spentFraction / elapsed) : 1;
    }

    default: return 1;
  }
}

// ─── Analytics helpers ────────────────────────────────────────────────────────

export function getPacingState(adId: string): PacingState {
  return loadPacingState(adId);
}

export function calculateECPM(totalRevenue: number, impressions: number): number {
  if (impressions === 0) return 0;
  return (totalRevenue / impressions) * 1000;
}

export function calculateViewableCPM(totalRevenue: number, viewableImpressions: number): number {
  if (viewableImpressions === 0) return 0;
  return (totalRevenue / viewableImpressions) * 1000;
}

export function formatCTR(clicks: number, impressions: number): string {
  if (impressions === 0) return "0.00%";
  return `${((clicks / impressions) * 100).toFixed(2)}%`;
}

export function formatCVR(conversions: number, clicks: number): string {
  if (clicks === 0) return "0.00%";
  return `${((conversions / clicks) * 100).toFixed(2)}%`;
}

export function formatROAS(revenue: number, spend: number): string {
  if (spend === 0) return "0.00x";
  return `${(revenue / spend).toFixed(2)}x`;
}

export function formatPrice(amount: number, currency = "INR"): string {
  const major = amount / 100;
  return new Intl.NumberFormat("en-IN", {
    style: "currency", currency,
    minimumFractionDigits: 2,
  }).format(major);
}

export function pricingModelLabel(model: PricingModel): string {
  const labels: Record<PricingModel, string> = {
    CPM: "Cost per 1,000 Impressions",
    CPC: "Cost per Click",
    CPV: "Cost per View",
    CPA: "Cost per Acquisition",
    CPL: "Cost per Lead",
    CPI: "Cost per Install",
    CPE: "Cost per Engagement",
    CPD: "Cost per Day",
    CPT: "Cost per Thousand",
    flat_rate: "Flat Rate",
    vCPM: "Viewable CPM",
    CPCV: "Cost per Completed View",
    CPF: "Cost per Fold",
    AVOC: "Audible & Viewable on Complete",
    ROAS: "Return on Ad Spend",
    CPS: "Cost per Sale",
    CPO: "Cost per Order",
    CPR: "Cost per Registration",
    CPTM: "Cost per Targeted Thousand",
    CPQ: "Cost per Qualified Lead",
    CPC_enhanced: "Enhanced CPC",
    smart_cpa: "Smart Target CPA",
    target_roas: "Target ROAS",
    max_conversions: "Maximise Conversions",
    first_price_auction: "First-Price Auction",
    second_price_auction: "Second-Price Auction",
    header_bidding_cpm: "Header Bidding CPM",
    open_rtb_cpm: "OpenRTB CPM",
    private_deal_cpm: "Private Marketplace CPM",
    preferred_deal_cpm: "Preferred Deal CPM",
    programmatic_guaranteed_cpm: "Programmatic Guaranteed CPM",
    dynamic_floor: "Dynamic Floor CPM",
    yield_optimised: "Yield-Optimised CPM",
    revenue_share: "Revenue Share",
    profit_share: "Profit Share",
    gross_margin_share: "Gross Margin Share",
    CPH: "Cost per Hour",
    tenancy_day: "Daily Tenancy",
    tenancy_week: "Weekly Tenancy",
    tenancy_month: "Monthly Tenancy",
    burst_rate: "Burst / Surge Rate",
    hybrid_cpm_cpc: "Hybrid CPM + CPC",
    hybrid_cpa_cpv: "Hybrid CPV + CPA",
    stepped_cpm: "Volume-Stepped CPM",
    tiered_cpc: "Quality-Tiered CPC",
    bid_shading: "Bid Shading",
    subscription_monthly: "Monthly Subscription",
    subscription_annual: "Annual Subscription",
    credit_bundle: "Credit Bundle",
    prepaid_credits: "Prepaid Credits",
    pay_for_results: "Pay for Results",
    cost_per_attention: "Cost per Attention Second",
    cost_per_memory: "Cost per Brand Recall",
    guaranteed_outcomes: "Guaranteed Outcomes",
  };
  return labels[model] ?? model;
}

export function pricingModelCategory(model: PricingModel): string {
  const categories: Partial<Record<PricingModel, string>> = {
    CPM: "Display",
    vCPM: "Display",
    CPC: "Performance",
    CPA: "Performance",
    CPL: "Performance",
    CPV: "Video",
    CPCV: "Video",
    AVOC: "Video",
    first_price_auction: "Programmatic",
    second_price_auction: "Programmatic",
    header_bidding_cpm: "Programmatic",
    open_rtb_cpm: "Programmatic",
    revenue_share: "Partnership",
    subscription_monthly: "Tenancy",
    tenancy_day: "Tenancy",
    flat_rate: "Tenancy",
    ROAS: "AI/Smart",
    smart_cpa: "AI/Smart",
    target_roas: "AI/Smart",
    max_conversions: "AI/Smart",
  };
  return categories[model] ?? "Other";
}
