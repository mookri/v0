import type { Ad, PricingModel } from "@/components/ad-network/core/types";
import { layoutRegistry } from "./LayoutRegistry";
import type { RankedCandidate, RankingContext, RankingWeights } from "./types";

const DEFAULT_WEIGHTS: RankingWeights = {
  revenue: 0.55,
  ctr: 0.2,
  relevance: 0.15,
  quality: 0.1,
  uxPenalty: 0.2,
};

function clamp(value: number, min = 0, max = 1): number {
  return Math.min(max, Math.max(min, value));
}

function pricingModelFor(ad: Ad): PricingModel | undefined {
  return ad.budget?.model;
}

function predictCtr(ad: Ad, ctx: RankingContext): number {
  const base = ad.type === "native" ? 0.014 : ad.type === "house" ? 0.018 : ad.type === "display" ? 0.008 : 0.01;
  const verifiedBoost = ad.verified ? 0.002 : 0;
  const tagBoost = (ad.tags?.some((tag) => (ctx.page.keywords ?? []).includes(tag)) ? 0.003 : 0);
  const engagementBoost = ctx.session.engagementScore * 0.004;
  const returningBoost = ctx.user.isReturning ? 0.0015 : 0;
  const fatiguePenalty = Math.min(0.005, (ctx.priorSponsorCounts[ad.sponsorName ?? ad.id] ?? 0) * 0.0015);
  return clamp(base + verifiedBoost + tagBoost + engagementBoost + returningBoost - fatiguePenalty, 0.002, 0.08);
}

function predictCvr(ad: Ad, ctx: RankingContext): number {
  const transactionalIntent = ctx.page.utmMedium === "cpc" || ctx.page.categories?.includes("services");
  const base = ad.type === "house" || ad.type === "direct" ? 0.0045 : 0.0025;
  return clamp(base + (transactionalIntent ? 0.002 : 0) + (ctx.user.isReturning ? 0.001 : 0), 0.0005, 0.04);
}

function computeRelevance(ad: Ad, ctx: RankingContext): number {
  const pageTerms = new Set(
    [
      ...(ctx.page.keywords ?? []),
      ...(ctx.page.categories ?? []),
      ...(ctx.page.title ? ctx.page.title.toLowerCase().split(/\s+/) : []),
    ].map((term) => term.toLowerCase()),
  );
  const adTerms = new Set([...(ad.tags ?? []), ...(ad.categories ?? []), ad.title, ad.description ?? ""].join(" ").toLowerCase().split(/\s+/));

  let matches = 0;
  for (const term of adTerms) {
    if (term && pageTerms.has(term)) matches++;
  }

  const denominator = Math.max(6, adTerms.size);
  const textualMatch = matches / denominator;
  const placementBonus = ad.placements.includes(ctx.opportunity.placementHints[0]) ? 0.15 : 0;
  return clamp(0.25 + textualMatch + placementBonus, 0.1, 1);
}

function computeQuality(ad: Ad): number {
  const existing = typeof ad.qualityScore === "number" ? ad.qualityScore / 100 : 0.45;
  const creativeCompleteness =
    (ad.creative?.imageUrl ? 0.12 : 0) +
    (ad.creative?.imageUrlWebp ? 0.05 : 0) +
    (ad.creative?.alt ? 0.04 : 0) +
    (ad.description ? 0.06 : 0) +
    (ad.verified ? 0.06 : 0);
  return clamp(existing + creativeCompleteness, 0.2, 1);
}

function layoutSignals(ad: Ad) {
  return ad.layouts.map((layout) => ({
    layout,
    spec: layoutRegistry.getSpec(layout),
  }));
}

function opportunityPreferencePenalty(ad: Ad, ctx: RankingContext): number {
  const signals = layoutSignals(ad);
  let penalty = 0;

  if (
    ctx.opportunity.preferredFamilies?.length &&
    !signals.some(({ spec }) => ctx.opportunity.preferredFamilies?.includes(spec.family))
  ) {
    penalty += 0.16;
  }

  if (
    ctx.opportunity.preferredTemplates?.length &&
    !signals.some(({ spec }) => ctx.opportunity.preferredTemplates?.includes(spec.template))
  ) {
    penalty += 0.18;
  }

  if (ctx.opportunity.designIntent === "compact") {
    const hasCompactOption = signals.some(({ spec }) => spec.density === "compact" || spec.template === "split");
    if (!hasCompactOption) penalty += 0.08;
  }

  if (ctx.opportunity.designIntent === "immersive") {
    const hasImmersiveOption = signals.some(({ spec }) => spec.density === "immersive" || spec.template === "poster");
    if (!hasImmersiveOption) penalty += 0.1;
  }

  if (ctx.opportunity.designIntent === "editorial") {
    const hasEditorialOption = signals.some(({ spec }) => spec.family === "native" || spec.family === "sponsored");
    if (!hasEditorialOption) penalty += 0.08;
  }

  return penalty;
}

function diversityPenalty(ad: Ad, ctx: RankingContext): number {
  const signals = layoutSignals(ad);
  if (signals.length === 0) return 0;

  const bestLayoutPenalty = Math.min(...signals.map(({ layout }) => (ctx.priorLayoutCounts[layout] ?? 0) * 0.05));
  const bestFamilyPenalty = Math.min(...signals.map(({ spec }) => (ctx.priorFamilyCounts[spec.family] ?? 0) * 0.08));
  const bestTemplatePenalty = Math.min(...signals.map(({ spec }) => (ctx.priorTemplateCounts[spec.template] ?? 0) * 0.09));

  return Math.min(0.36, bestLayoutPenalty + bestFamilyPenalty + bestTemplatePenalty);
}

function preferenceBoost(ad: Ad, ctx: RankingContext): number {
  const signals = layoutSignals(ad);
  let boost = 0;

  if (ctx.opportunity.preferredFamilies?.length && signals.some(({ spec }) => ctx.opportunity.preferredFamilies?.includes(spec.family))) {
    boost += 0.05;
  }

  if (ctx.opportunity.preferredTemplates?.length && signals.some(({ spec }) => ctx.opportunity.preferredTemplates?.includes(spec.template))) {
    boost += 0.06;
  }

  return boost;
}

function computeUxPenalty(ad: Ad, ctx: RankingContext): number {
  const repeatPenalty = Math.min(0.5, (ctx.priorSponsorCounts[ad.sponsorName ?? ad.id] ?? 0) * 0.12);
  const depthPenalty = ctx.opportunity.depthBucket === "above_fold" && ad.layouts.some((layout) => layout.startsWith("hero")) ? 0.08 : 0;
  const intrusivePenalty = ad.layouts.some((layout) => layout.includes("sticky") || layout.includes("interstitial")) ? 0.25 : 0;
  const lowBandwidthPenalty = ctx.client.saveData && !ad.layouts.includes("low_bandwidth") ? 0.18 : 0;
  const stylePenalty = opportunityPreferencePenalty(ad, ctx);
  const repeatStylePenalty = diversityPenalty(ad, ctx);
  const boost = preferenceBoost(ad, ctx);
  return clamp(repeatPenalty + depthPenalty + intrusivePenalty + lowBandwidthPenalty + stylePenalty + repeatStylePenalty - boost, 0, 0.95);
}

function ecpmFromOutcomeBid(ad: Ad, predictedCtr: number, predictedCvr: number): number {
  const bid = ad.budget?.bidFloor ?? 0;
  const model = pricingModelFor(ad);

  switch (model) {
    case "CPM":
    case "vCPM":
    case "first_price_auction":
    case "second_price_auction":
    case "header_bidding_cpm":
    case "open_rtb_cpm":
    case "private_deal_cpm":
    case "preferred_deal_cpm":
    case "programmatic_guaranteed_cpm":
    case "dynamic_floor":
    case "yield_optimised":
      return bid;
    case "CPC":
    case "CPC_enhanced":
    case "tiered_cpc":
      return bid * predictedCtr * 1000;
    case "CPA":
    case "CPL":
    case "CPI":
    case "CPO":
    case "CPQ":
    case "smart_cpa":
    case "pay_for_results":
    case "guaranteed_outcomes":
      return bid * predictedCvr * 1000;
    case "ROAS":
    case "target_roas":
    case "max_conversions":
      return bid * predictedCvr * 1200;
    case "hybrid_cpm_cpc":
      return bid * 0.6 + bid * predictedCtr * 400;
    case "hybrid_cpa_cpv":
      return bid * predictedCvr * 800 + bid * 0.2;
    case "flat_rate":
    case "CPD":
    case "tenancy_day":
    case "tenancy_week":
    case "tenancy_month":
      return bid / 50;
    default:
      return bid || 100;
  }
}

export class RankingEngine {
  constructor(private readonly weights: RankingWeights = DEFAULT_WEIGHTS) {}

  rank(candidates: Ad[], ctx: RankingContext): RankedCandidate[] {
    return candidates
      .map((ad) => {
        const predictedCtr = predictCtr(ad, ctx);
        const predictedCvr = predictCvr(ad, ctx);
        const relevance = computeRelevance(ad, ctx);
        const qualityScore = computeQuality(ad);
        const revenueEcpm = ecpmFromOutcomeBid(ad, predictedCtr, predictedCvr);
        const uxPenalty = computeUxPenalty(ad, ctx);
        const totalScore =
          this.weights.revenue * (revenueEcpm / 1000) +
          this.weights.ctr * predictedCtr +
          this.weights.relevance * relevance +
          this.weights.quality * qualityScore -
          this.weights.uxPenalty * uxPenalty;

        return {
          ad,
          predictedCtr,
          predictedCvr,
          relevance,
          qualityScore,
          revenueEcpm,
          uxPenalty,
          totalScore,
        };
      })
      .sort((left, right) => right.totalScore - left.totalScore);
  }
}
