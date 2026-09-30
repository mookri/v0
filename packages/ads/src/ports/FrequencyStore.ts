export interface FrequencyStore {
  getCount(key: string): Promise<number>;
  increment(key: string): Promise<void>;
}
