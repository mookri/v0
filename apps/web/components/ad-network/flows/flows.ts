/* ═══════════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  flows.ts
   20+ prebuilt campaign flows · Trigger-based activation · Sequential steps
   Auto-adaptation · Exit conditions · KPI tracking
   ═══════════════════════════════════════════════════════════════════════════════ */

import { PrebuiltFlow, PrebuiltFlowType, FlowStep, FlowTrigger, FlowExitCondition } from "@/components/ad-network/core/types";

export type FlowDefinition = PrebuiltFlow;

// ─── Flow registry ────────────────────────────────────────────────────────────

const FLOW_REGISTRY = new Map<string, PrebuiltFlow>();

export function registerFlow(flow: PrebuiltFlow): void {
  FLOW_REGISTRY.set(flow.id, flow);
}

export function getFlow(id: string): PrebuiltFlow | undefined {
  return FLOW_REGISTRY.get(id);
}

export function getFlowsByType(type: PrebuiltFlowType): PrebuiltFlow[] {
  return [...FLOW_REGISTRY.values()].filter(f => f.type === type);
}

// ─── Prebuilt flows ───────────────────────────────────────────────────────────

export const PREBUILT_FLOWS: PrebuiltFlow[] = [
  // ── 1. New User Onboarding ───────────────────────────────────────────────────
  {
    id: "flow_new_user_onboarding",
    type: "new_user_onboarding",
    name: "New User Onboarding",
    description: "Progressive reveal campaign for first-time visitors. Show brand awareness first, then product-specific, then conversion.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 3 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "viewable", waitMs: 2000, maxAttempts: 5 },
      { id: "s3", order: 3, adIds: [], waitAfterEventType: "click", waitMs: 0, maxAttempts: 10 },
    ],
    triggers: [{ event: "page_view", conditions: [{ field: "visitCount", operator: "lte", value: 1 }] }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["impressions", "clicks", "ctr", "conversion_rate"],
  },

  // ── 2. Cart Abandonment ──────────────────────────────────────────────────────
  {
    id: "flow_cart_abandonment",
    type: "cart_abandonment",
    name: "Cart Abandonment Recovery",
    description: "Re-engage users who added items to cart but didn't purchase. Urgency-driven creative sequence.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 2 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "impression", waitMs: 86_400_000, maxAttempts: 2 }, // Next day
      { id: "s3", order: 3, adIds: [], waitAfterEventType: "impression", waitMs: 172_800_000, maxAttempts: 3 }, // 2 days
    ],
    triggers: [{ event: "cart_add" }, { event: "checkout_start" }],
    exitConditions: [{ event: "conversion" }, { event: "close" }],
    kpis: ["recovery_rate", "revenue_recovered", "cpa"],
  },

  // ── 3. Retargeting Lapsed ────────────────────────────────────────────────────
  {
    id: "flow_retargeting_lapsed",
    type: "retargeting_lapsed",
    name: "Lapsed User Re-engagement",
    description: "Win back users who visited > 7 days ago but haven't returned. Progressive frequency.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 3 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "viewable", waitMs: 259_200_000, maxAttempts: 3 }, // 3 days
    ],
    triggers: [{ event: "page_view", conditions: [{ field: "daysSinceLastVisit", operator: "gte", value: 7 }] }],
    exitConditions: [{ event: "click" }, { event: "conversion" }],
    kpis: ["re_engagement_rate", "ctr", "cost_per_reactivation"],
  },

  // ── 4. Post-Conversion Upsell ────────────────────────────────────────────────
  {
    id: "flow_post_conversion",
    type: "post_conversion",
    name: "Post-Conversion Upsell",
    description: "Cross-sell and upsell to recent converters within 72h window.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 300_000, maxAttempts: 2 }, // 5 min
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "impression", waitMs: 3_600_000, maxAttempts: 3 }, // 1h
    ],
    triggers: [{ event: "conversion" }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["upsell_rate", "aov_lift", "revenue_per_user"],
  },

  // ── 5. Lookalike Acquisition ──────────────────────────────────────────────────
  {
    id: "flow_lookalike_acquisition",
    type: "lookalike_acquisition",
    name: "Lookalike Audience Acquisition",
    description: "Prospecting campaign targeting users who resemble top customers.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 5 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "viewable", waitMs: 7_200_000, maxAttempts: 3 },
      { id: "s3", order: 3, adIds: [], waitAfterEventType: "click", waitMs: 0, maxAttempts: 5 },
    ],
    triggers: [{ event: "session_start", conditions: [{ field: "isNewUser", operator: "eq", value: true }] }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["cpa", "ltv_predicted", "quality_score"],
  },

  // ── 6. Flash Sale ────────────────────────────────────────────────────────────
  {
    id: "flow_flash_sale",
    type: "flash_sale",
    name: "Flash Sale Blitz",
    description: "High-frequency urgency campaign for time-limited promotions. Max reach in 24h.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 8 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "impression", waitMs: 1_800_000, maxAttempts: 6 }, // 30 min
    ],
    triggers: [{ event: "page_view" }],
    exitConditions: [{ event: "conversion" }, { event: "close" }],
    kpis: ["impressions_24h", "conversion_rate", "revenue_24h"],
  },

  // ── 7. Brand Awareness ───────────────────────────────────────────────────────
  {
    id: "flow_brand_awareness",
    type: "brand_awareness",
    name: "Brand Awareness Campaign",
    description: "Top-of-funnel brand building. Prioritise viewability and frequency over clicks.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "viewable", waitMs: 86_400_000, maxAttempts: 2 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "viewable", waitMs: 86_400_000, maxAttempts: 2 },
      { id: "s3", order: 3, adIds: [], waitAfterEventType: "viewable", waitMs: 86_400_000, maxAttempts: 2 },
    ],
    triggers: [{ event: "page_view" }],
    exitConditions: [{ event: "click" }],
    kpis: ["viewable_impressions", "vcpm", "brand_recall_lift"],
  },

  // ── 8. App Install ───────────────────────────────────────────────────────────
  {
    id: "flow_app_install",
    type: "app_install",
    name: "Mobile App Install Campaign",
    description: "Drive app installs on mobile. Sequential formats: banner → interstitial → rewarded.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 3 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "impression", waitMs: 900_000, maxAttempts: 2 }, // 15 min
      { id: "s3", order: 3, adIds: [], waitAfterEventType: "viewable", waitMs: 1_800_000, maxAttempts: 1 }, // 30 min, rewarded
    ],
    triggers: [{ event: "session_start", conditions: [{ field: "deviceType", operator: "in", value: ["mobile", "tablet"] }] }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["cpi", "install_rate", "d7_retention"],
  },

  // ── 9. Lead Generation ───────────────────────────────────────────────────────
  {
    id: "flow_lead_generation",
    type: "lead_generation",
    name: "Lead Generation Funnel",
    description: "Convert anonymous visitors to known leads through progressive engagement.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 3 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "click", waitMs: 0, maxAttempts: 5 },
      { id: "s3", order: 3, adIds: [], waitAfterEventType: "form_start", waitMs: 0, maxAttempts: 3 },
    ],
    triggers: [{ event: "page_view", conditions: [{ field: "scrollDepth", operator: "gte", value: 0.5 }] }],
    exitConditions: [{ event: "form_submit" }, { event: "conversion" }],
    kpis: ["cpl", "lead_quality_score", "form_completion_rate"],
  },

  // ── 10. Seasonal Campaign ────────────────────────────────────────────────────
  {
    id: "flow_seasonal",
    type: "seasonal_campaign",
    name: "Seasonal Campaign",
    description: "Holiday/festival campaign with auto-dayparting and audience surge bidding.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 5 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "viewable", waitMs: 3_600_000, maxAttempts: 3 },
    ],
    triggers: [{ event: "page_view" }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["reach", "frequency", "revenue_per_impression"],
  },

  // ── 11. Loyalty Reward ───────────────────────────────────────────────────────
  {
    id: "flow_loyalty_reward",
    type: "loyalty_reward",
    name: "Loyalty Customer Rewards",
    description: "Exclusive ads for high-LTV loyal customers with premium offers.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 2 },
    ],
    triggers: [{ event: "session_start", conditions: [{ field: "ltv", operator: "gte", value: 5000 }] }],
    exitConditions: [{ event: "click" }],
    kpis: ["ctr", "offer_redemption_rate", "incremental_revenue"],
  },

  // ── 12. Event Promotion ──────────────────────────────────────────────────────
  {
    id: "flow_event_promotion",
    type: "event_promotion",
    name: "Live Event Promotion",
    description: "Drive event registrations with countdown-driven urgency.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 4 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "impression", waitMs: 43_200_000, maxAttempts: 2 }, // 12h
    ],
    triggers: [{ event: "page_view" }],
    exitConditions: [{ event: "conversion" }, { event: "timer_complete" }],
    kpis: ["registration_rate", "cost_per_registration", "show_rate"],
  },

  // ── 13. Contextual Relevance ─────────────────────────────────────────────────
  {
    id: "flow_contextual",
    type: "contextual_relevance",
    name: "Contextual Ad Matching",
    description: "Show ads matched to page content, no cookies required. GDPR-friendly.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 3 },
    ],
    triggers: [{ event: "page_view" }],
    exitConditions: [{ event: "click" }, { event: "conversion" }],
    kpis: ["relevance_score", "ctr", "viewability_rate"],
  },

  // ── 14. Re-engagement ────────────────────────────────────────────────────────
  {
    id: "flow_reengagement",
    type: "re_engagement",
    name: "App Re-engagement",
    description: "Re-activate dormant app users through deep-link campaigns.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 3 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "impression", waitMs: 172_800_000, maxAttempts: 2 },
    ],
    triggers: [{ event: "session_start", conditions: [{ field: "daysSinceLastAppOpen", operator: "gte", value: 14 }] }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["reactivation_rate", "session_depth", "d30_retention"],
  },

  // ── 15. Product Launch ───────────────────────────────────────────────────────
  {
    id: "flow_product_launch",
    type: "product_launch",
    name: "Product Launch Campaign",
    description: "Phased product launch: teaser → reveal → conversion push.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "viewable", waitMs: 86_400_000, maxAttempts: 3 }, // Teaser
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "viewable", waitMs: 86_400_000, maxAttempts: 3 }, // Reveal
      { id: "s3", order: 3, adIds: [], waitAfterEventType: "click", waitMs: 0, maxAttempts: 5 },             // Conversion
    ],
    triggers: [{ event: "page_view" }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["awareness_reach", "consideration_rate", "launch_day_revenue"],
  },

  // ── 16. Competitive Conquest ──────────────────────────────────────────────────
  {
    id: "flow_conquest",
    type: "competitive_conquest",
    name: "Competitive Conquest",
    description: "Target users of competitor products with comparison and switching ads.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 4 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "impression", waitMs: 7_200_000, maxAttempts: 3 },
    ],
    triggers: [{ event: "page_view", conditions: [{ field: "competitorKeywordMatch", operator: "eq", value: true }] }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["switch_rate", "cpa", "incremental_share"],
  },

  // ── 17. Geo Local ────────────────────────────────────────────────────────────
  {
    id: "flow_geo_local",
    type: "geo_local",
    name: "Local Geo-targeting Campaign",
    description: "Hyper-local ads based on city, pincode or radius.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 5 },
    ],
    triggers: [{ event: "session_start" }],
    exitConditions: [{ event: "click" }, { event: "conversion" }],
    kpis: ["local_ctr", "store_visit_rate", "local_cpa"],
  },

  // ── 18. Daypart-Optimised ────────────────────────────────────────────────────
  {
    id: "flow_daypart",
    type: "daypart_optimised",
    name: "Daypart Optimised Campaign",
    description: "Automatically show the right creative at the right time of day.",
    steps: [
      { id: "morning", order: 1, adIds: [], maxAttempts: 3 },
      { id: "afternoon", order: 2, adIds: [], maxAttempts: 3 },
      { id: "evening", order: 3, adIds: [], maxAttempts: 3 },
      { id: "night", order: 4, adIds: [], maxAttempts: 2 },
    ],
    triggers: [{ event: "page_view" }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["peak_hour_ctr", "off_peak_ctr", "daypart_efficiency"],
  },

  // ── 19. Upsell Cross-sell ────────────────────────────────────────────────────
  {
    id: "flow_upsell_crosssell",
    type: "upsell_crosssell",
    name: "Upsell & Cross-sell",
    description: "Dynamic product recommendations based on purchase history and browsing.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 3 },
      { id: "s2", order: 2, adIds: [], waitAfterEventType: "impression", waitMs: 1_800_000, maxAttempts: 2 },
    ],
    triggers: [{ event: "page_view", conditions: [{ field: "hasPurchaseHistory", operator: "eq", value: true }] }],
    exitConditions: [{ event: "conversion" }],
    kpis: ["upsell_rate", "aov", "revenue_per_visitor"],
  },

  // ── 20. Content Recommendation ───────────────────────────────────────────────
  {
    id: "flow_content_recommendation",
    type: "content_recommendation",
    name: "Sponsored Content Recommendation",
    description: "Native content recommendations that blend with editorial flow.",
    steps: [
      { id: "s1", order: 1, adIds: [], waitAfterEventType: "impression", waitMs: 0, maxAttempts: 6 },
    ],
    triggers: [{ event: "scroll_to", conditions: [{ field: "scrollDepth", operator: "gte", value: 0.8 }] }],
    exitConditions: [{ event: "click" }],
    kpis: ["engagement_rate", "time_on_content", "content_ctr"],
  },
];

// ─── Register all prebuilt flows ──────────────────────────────────────────────
PREBUILT_FLOWS.forEach(registerFlow);

// ─── Flow state machine ───────────────────────────────────────────────────────

export interface FlowState {
  flowId: string;
  userId: string;
  currentStep: number;
  startedAt: number;
  completedSteps: string[];
  exitedAt?: number;
  exitEvent?: string;
  active: boolean;
}

const FLOW_STATE_PREFIX = "ad_flow_state_";

export function getFlowState(flowId: string, userId: string): FlowState | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = localStorage.getItem(`${FLOW_STATE_PREFIX}${flowId}_${userId}`);
    return raw ? JSON.parse(raw) : null;
  } catch { return null; }
}

export function saveFlowState(state: FlowState): void {
  if (typeof window === "undefined") return;
  try {
    localStorage.setItem(`${FLOW_STATE_PREFIX}${state.flowId}_${state.userId}`, JSON.stringify(state));
  } catch { /**/ }
}

export function startFlow(flowId: string, userId: string): FlowState {
  const state: FlowState = {
    flowId, userId,
    currentStep: 0,
    startedAt: 1700000000000,
    completedSteps: [],
    active: true,
  };
  saveFlowState(state);
  return state;
}

export function advanceFlow(flowId: string, userId: string): FlowState | null {
  const flow = getFlow(flowId);
  const state = getFlowState(flowId, userId);
  if (!flow || !state || !state.active) return null;

  const currentStepDef = flow.steps[state.currentStep];
  if (currentStepDef) state.completedSteps.push(currentStepDef.id);

  state.currentStep++;

  if (state.currentStep >= flow.steps.length) {
    state.active = false;
    state.exitedAt = 1700000000000;
    state.exitEvent = "flow_complete";
  }

  saveFlowState(state);
  return state;
}

export function exitFlow(flowId: string, userId: string, exitEvent: string): void {
  const state = getFlowState(flowId, userId);
  if (!state) return;
  state.active = false;
  state.exitedAt = 1700000000000;
  state.exitEvent = exitEvent;
  saveFlowState(state);
}

export function getCurrentFlowStep(flowId: string, userId: string): FlowStep | null {
  const flow = getFlow(flowId);
  const state = getFlowState(flowId, userId);
  if (!flow || !state || !state.active) return null;
  return flow.steps[state.currentStep] ?? null;
}

export function getEligibleAdIdsForFlow(flowId: string, userId: string): string[] {
  const step = getCurrentFlowStep(flowId, userId);
  return step?.adIds ?? [];
}
