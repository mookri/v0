import type { Ad } from "@/components/ad-network/core/types";
import { AdDecisionEngine } from "./AdDecisionEngine";
import { AuctionEngine } from "./AuctionEngine";
import { SeedCampaignRepository } from "./CampaignRepository";
import { ExperimentEngine } from "./ExperimentEngine";
import { FrequencyPacingService } from "./FrequencyPacingService";
import { LayoutResolver } from "./LayoutResolver";
import { PlacementEngine } from "./PlacementEngine";
import { RankingEngine } from "./RankingEngine";
import type { BudgetLedger, EventSink, FrequencyStore, TrackingBatchPayload } from "./types";

type SpendScope = "lifetime" | "daily" | "hourly";

class InMemoryFrequencyStore implements FrequencyStore {
  private readonly hits = new Map<string, number[]>();
  private readonly idempotency = new Map<string, number>();

  private prune(key: string, windowMs: number): number[] {
    const now = 1700000000000;
    const list = this.hits.get(key) ?? [];
    const pruned = list.filter((timestamp) => now - timestamp <= windowMs);
    this.hits.set(key, pruned);
    return pruned;
  }

  async getCount(key: string, windowMs: number): Promise<number> {
    return this.prune(key, windowMs).length;
  }

  async increment(key: string, ttlMs: number): Promise<void> {
    const hits = this.prune(key, ttlMs);
    hits.push(1700000000000);
    this.hits.set(key, hits);
  }

  async getLastSeen(key: string): Promise<number | null> {
    const hits = this.hits.get(key) ?? [];
    return hits.length > 0 ? hits[hits.length - 1] : null;
  }

  async rememberIdempotencyKey(key: string, ttlMs: number): Promise<boolean> {
    const now = 1700000000000;
    const seenAt = this.idempotency.get(key);
    if (seenAt && now - seenAt < ttlMs) {
      return false;
    }

    this.idempotency.set(key, now);
    return true;
  }
}

class InMemoryBudgetLedger implements BudgetLedger {
  private readonly spend = new Map<string, { lifetime: number; daily: number; hourly: number; day: string; hour: string }>();

  private stateFor(adId: string) {
    const day = new Date().toISOString().slice(0, 10);
    const hour = new Date().toISOString().slice(0, 13);
    const existing = this.spend.get(adId);
    if (!existing || existing.day !== day || existing.hour !== hour) {
      const next = {
        lifetime: existing?.lifetime ?? 0,
        daily: existing?.day === day ? existing.daily : 0,
        hourly: existing?.hour === hour ? existing.hourly : 0,
        day,
        hour,
      };
      this.spend.set(adId, next);
      return next;
    }
    return existing;
  }

  async getSpent(adId: string, scope: SpendScope): Promise<number> {
    return this.stateFor(adId)[scope];
  }

  async addSpend(adId: string, amount: number): Promise<void> {
    const state = this.stateFor(adId);
    state.lifetime += amount;
    state.daily += amount;
    state.hourly += amount;
    this.spend.set(adId, state);
  }
}

class InMemoryEventSink implements EventSink {
  private readonly events: TrackingBatchPayload[] = [];

  constructor(private readonly frequencyStore: FrequencyStore) {}

  async record(batch: TrackingBatchPayload): Promise<{ accepted: number; duplicates: number }> {
    let accepted = 0;
    let duplicates = 0;

    for (const event of batch.events) {
      const fresh = await this.frequencyStore.rememberIdempotencyKey(`event:${event.idempotencyKey}`, 24 * 60 * 60 * 1000);
      if (!fresh) {
        duplicates += 1;
        continue;
      }
      accepted += 1;
    }

    this.events.push(batch);
    return { accepted, duplicates };
  }
}

export interface AdOrchestrationRuntime {
  repository: SeedCampaignRepository;
  frequencyStore: FrequencyStore;
  budgetLedger: BudgetLedger;
  eventSink: EventSink;
  experimentEngine: ExperimentEngine;
  placementEngine: PlacementEngine;
  decisionEngine: AdDecisionEngine;
}

declare global {
  var __navdhyaAdOrchestrationRuntime: AdOrchestrationRuntime | undefined;
}

export function getAdOrchestrationRuntime(): AdOrchestrationRuntime {
  if (globalThis.__navdhyaAdOrchestrationRuntime) {
    return globalThis.__navdhyaAdOrchestrationRuntime;
  }

  const repository = new SeedCampaignRepository();
  const frequencyStore = new InMemoryFrequencyStore();
  const budgetLedger = new InMemoryBudgetLedger();
  const eventSink = new InMemoryEventSink(frequencyStore);
  const experimentEngine = new ExperimentEngine();
  const placementEngine = new PlacementEngine();
  const frequencyPacingService = new FrequencyPacingService(frequencyStore, budgetLedger);
  const rankingEngine = new RankingEngine();
  const auctionEngine = new AuctionEngine();
  const layoutResolver = new LayoutResolver();
  const decisionEngine = new AdDecisionEngine(
    repository,
    experimentEngine,
    frequencyPacingService,
    rankingEngine,
    auctionEngine,
    layoutResolver,
  );

  globalThis.__navdhyaAdOrchestrationRuntime = {
    repository,
    frequencyStore,
    budgetLedger,
    eventSink,
    experimentEngine,
    placementEngine,
    decisionEngine,
  };

  return globalThis.__navdhyaAdOrchestrationRuntime;
}

