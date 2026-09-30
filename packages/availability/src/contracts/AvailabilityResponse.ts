import { AvailabilitySlot } from '@navdhya/availability/src/domain/AvailabilitySlot';

export interface AvailabilityResponse {
  resourceId: string;
  availableSlots: AvailabilitySlot[];
}
