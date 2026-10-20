import { SlotId } from './SlotId';

export interface AvailabilitySlot {
  id: SlotId;
  resourceId: string; // PanditId or similar
  startTime: Date;
  endTime: Date;
  isReserved: boolean;
}
