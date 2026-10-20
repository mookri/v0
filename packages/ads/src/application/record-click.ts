import { AdEvent, TrackingRepository } from '@navdhya/ads/src/ports/TrackingRepository';

export async function recordClick(repo: TrackingRepository, event: AdEvent): Promise<void> {
  if (event.type !== 'click') throw new Error('recordClick requires type=click');
  await repo.record(event);
}
