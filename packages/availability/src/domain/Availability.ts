import { AvailabilitySlot } from './AvailabilitySlot';

export interface Availability {
  resourceId: string;
  slots: AvailabilitySlot[];
}
