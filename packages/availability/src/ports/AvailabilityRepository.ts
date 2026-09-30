import { AvailabilitySlot } from '@navdhya/availability/src/domain/AvailabilitySlot';
import { SlotId } from '@navdhya/availability/src/domain/SlotId';

export interface AvailabilityRepository {
  findSlots(resourceId: string, start: Date, end: Date): Promise<AvailabilitySlot[]>;
  findById(id: SlotId): Promise<AvailabilitySlot | null>;
  update(slot: AvailabilitySlot): Promise<void>;
}
