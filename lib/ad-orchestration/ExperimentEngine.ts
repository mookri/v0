import type { Ad } from "@/components/ad-network/core/types";
import type { TrackingEventPayload } from "./types";

interface VariantStats {
  impressions: number;
  clicks: number;
  conversions: number;
  revenue: number;
}

interface ExperimentState {
  variants: Map<string, VariantStats>;
}

declare global {
  var __navdhyaExperimentState: Map<string, ExperimentState> | undefined;
}

function getExperimentStore(): Map<string, ExperimentState> {
  if (!globalThis.__navdhyaExperimentState) {
    globalThis.__navdhyaExperimentState = new Map<string, ExperimentState>();
  }
  return globalThis.__navdhyaExperimentState;
}

function hashToUnitInterval(value: string): number {
  let hash = 0;
  for (let index = 0; index < value.length; index += 1) {
    hash = (hash << 5) - hash + value.charCodeAt(index);
    hash |= 0;
  }
  return Math.abs(hash % 10000) / 10000;
}

function ensureState(key: string, variant: string): VariantStats {
  const store = getExperimentStore();
  const state = store.get(key) ?? { variants: new Map<string, VariantStats>() };
  store.set(key, state);
  const existing = state.variants.get(variant) ?? { impressions: 0, clicks: 0, conversions: 0, revenue: 0 };
  state.variants.set(variant, existing);
  return existing;
}

function scoreBandit(key: string, variant: string): number {
  const stats = ensureState(key, variant);
  if (stats.impressions === 0) return 1;

  const ctr = stats.clicks / stats.impressions;
  const revenuePerImpression = stats.revenue / stats.impressions;
  return ctr + revenuePerImpression / 1000 + Math.sqrt(2 * Math.log(stats.impressions + 1) / stats.impressions);
}

export class ExperimentEngine {
  assign(ads: Ad[], identity: string): { ads: Ad[]; assignments: Record<string, string> } {
    const grouped = new Map<string, Ad[]>();
    const withoutExperiment: Ad[] = [];

    for (const ad of ads) {
      if (!ad.experiment?.key || !ad.experiment.variant) {
        withoutExperiment.push(ad);
        continue;
      }

      const group = grouped.get(ad.experiment.key) ?? [];
      group.push(ad);
      grouped.set(ad.experiment.key, group);
    }

    const assignments: Record<string, string> = {};
    const selected: Ad[] = [...withoutExperiment];

    for (const [key, candidates] of grouped.entries()) {
      const holdbackPct = candidates[0]?.experiment?.holdbackPct ?? 0.1;
      if (hashToUnitInterval(`${identity}:${key}:holdback`) < holdbackPct) {
        assignments[key] = "holdback";
        continue;
      }

      const banditEnabled = candidates.some((ad) => ad.experiment?.type === "bandit" && ad.experiment.bandit);
      let assignedVariant = candidates[0]?.experiment?.variant ?? "A";

      if (banditEnabled) {
        assignedVariant = [...candidates]
          .sort((left, right) => scoreBandit(key, right.experiment?.variant ?? "A") - scoreBandit(key, left.experiment?.variant ?? "A"))[0]
          ?.experiment?.variant ?? assignedVariant;
      } else {
        const allocationRoll = hashToUnitInterval(`${identity}:${key}`);
        let cumulative = 0;
        for (const candidate of candidates) {
          cumulative += candidate.experiment?.trafficAllocation ?? 1 / candidates.length;
          if (allocationRoll <= cumulative) {
            assignedVariant = candidate.experiment?.variant ?? assignedVariant;
            break;
          }
        }
      }

      assignments[key] = assignedVariant;
      selected.push(...candidates.filter((candidate) => candidate.experiment?.variant === assignedVariant));
    }

    return { ads: selected, assignments };
  }

  record(event: TrackingEventPayload): void {
    for (const [key, variant] of Object.entries(event.experimentAssignments)) {
      const stats = ensureState(key, variant);
      if (event.type === "render" || event.type === "impression") stats.impressions += 1;
      if (event.type === "click") stats.clicks += 1;
      if (event.type === "conversion") stats.conversions += 1;
      stats.revenue += Number(event.customData?.revenue ?? 0);
    }
  }
}

