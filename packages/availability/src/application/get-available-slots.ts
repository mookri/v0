import { AvailabilityRepository } from '@navdhya/availability/src/ports/AvailabilityRepository';
import { AvailabilityQuery } from '@navdhya/availability/src/contracts/AvailabilityQuery';
import { AvailabilityResponse } from '@navdhya/availability/src/contracts/AvailabilityResponse';
import { isSlotAvailable } from '@navdhya/availability/src/domain/availability-rules';

export async function getAvailableSlots(repo: AvailabilityRepository, query: AvailabilityQuery): Promise<AvailabilityResponse> {
  const slots = await repo.findSlots(query.resourceId, query.startDate, query.endDate);
  return {
    resourceId: query.resourceId,
    availableSlots: slots.filter(isSlotAvailable),
  };
}
