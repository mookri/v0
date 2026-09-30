import type { Ad } from "@/components/ad-network/core/types";
import type { BudgetLedger, DecisionRequest, FrequencyStore, PlacementOpportunity } from "./types";

function buildIdentity(request: DecisionRequest): string {
  return request.user.userId ?? request.user.anonymousId ?? request.user.sessionId ?? request.session.id;
}

export class FrequencyPacingService {
  constructor(
    private readonly frequencyStore: FrequencyStore,
    private readonly budgetLedger: BudgetLedger,
  ) {}

  async isEligible(ad: Ad, request: DecisionRequest, opportunity: PlacementOpportunity): Promise<boolean> {
    const identity = buildIdentity(request);
    const profile = ad.frequencyProfile;
    if (profile) {
      const checkWindow = async (maxImpressions: number | undefined, windowSeconds: number | undefined) => {
        if (!maxImpressions || !windowSeconds) return true;
        const count = await this.frequencyStore.getCount(`${identity}:${ad.id}:${opportunity.slotId}`, windowSeconds * 1000);
        return count < maxImpressions;
      };

      if (!(await checkWindow(profile.hourly?.maxImpressions, profile.hourly?.windowSeconds))) return false;
      if (!(await checkWindow(profile.daily?.maxImpressions, profile.daily?.windowSeconds))) return false;
      if (!(await checkWindow(profile.weekly?.maxImpressions, profile.weekly?.windowSeconds))) return false;
      if (!(await checkWindow(profile.lifetime?.maxImpressions, profile.lifetime?.windowSeconds))) return false;

      if (profile.recencyMinutes) {
        const lastSeen = await this.frequencyStore.getLastSeen(`${identity}:${ad.id}:${opportunity.slotId}`);
        if (lastSeen && 1700000000000 - lastSeen < profile.recencyMinutes * 60_000) {
          return false;
        }
      }
    }

    const budget = ad.budget;
    if (budget) {
      if (budget.totalBudget) {
        const spentLifetime = await this.budgetLedger.getSpent(ad.id, "lifetime");
        if (spentLifetime >= budget.totalBudget) return false;
      }
      if (budget.dailyCap) {
        const spentDaily = await this.budgetLedger.getSpent(ad.id, "daily");
        if (spentDaily >= budget.dailyCap) return false;
      }
      if (budget.hourlyCap) {
        const spentHourly = await this.budgetLedger.getSpent(ad.id, "hourly");
        if (spentHourly >= budget.hourlyCap) return false;
      }
    }

    return true;
  }

  async recordImpression(ad: Ad, request: DecisionRequest, opportunity: PlacementOpportunity, amount: number): Promise<void> {
    const identity = buildIdentity(request);
    const profile = ad.frequencyProfile;
    const keys = [
      `${identity}:${ad.id}:${opportunity.slotId}`,
      `${identity}:${ad.id}:global`,
    ];

    if (profile?.hourly?.windowSeconds) {
      await Promise.all(keys.map((key) => this.frequencyStore.increment(key, profile.hourly!.windowSeconds * 1000)));
    } else {
      await Promise.all(keys.map((key) => this.frequencyStore.increment(key, 24 * 60 * 60 * 1000)));
    }

    await this.budgetLedger.addSpend(ad.id, amount);
  }
}

