import { FrequencyStore } from '@navdhya/ads/src/ports/FrequencyStore';

export async function checkFrequency(
  store: FrequencyStore,
  key: string,
  maxImpressions: number,
): Promise<boolean> {
  const count = await store.getCount(key);
  return count < maxImpressions;
}
