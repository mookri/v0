import { AvailabilitySlot } from '../domain/AvailabilitySlot';

export interface AvailabilityResponse {
  resourceId: string;
  availableSlots: AvailabilitySlot[];
}
