import { AdEvent, TrackingRepository } from '../ports/TrackingRepository';

export async function recordImpression(repo: TrackingRepository, event: AdEvent): Promise<void> {
  if (event.type !== 'impression') throw new Error('recordImpression requires type=impression');
  await repo.record(event);
}
