import { AvailabilityRepository } from '../ports/AvailabilityRepository';
import { AvailabilityQuery } from '../contracts/AvailabilityQuery';
import { AvailabilityResponse } from '../contracts/AvailabilityResponse';
import { isSlotAvailable } from '../domain/availability-rules';

export async function getAvailableSlots(repo: AvailabilityRepository, query: AvailabilityQuery): Promise<AvailabilityResponse> {
  const slots = await repo.findSlots(query.resourceId, query.startDate, query.endDate);
  return {
    resourceId: query.resourceId,
    availableSlots: slots.filter(isSlotAvailable),
  };
}
