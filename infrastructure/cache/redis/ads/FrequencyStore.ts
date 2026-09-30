import type { FrequencyStore } from '@infra/packages/ads/src/ports/FrequencyStore';

export class MemoryFrequencyStore implements FrequencyStore {
  private readonly counts = new Map<string, number>();

  async getCount(key: string): Promise<number> {
    return this.counts.get(key) ?? 0;
  }

  async increment(key: string): Promise<void> {
    this.counts.set(key, (this.counts.get(key) ?? 0) + 1);
  }
}
