import { AvailabilitySlot } from '../domain/AvailabilitySlot';
import { SlotId } from '../domain/SlotId';

export interface AvailabilityRepository {
  findSlots(resourceId: string, start: Date, end: Date): Promise<AvailabilitySlot[]>;
  findById(id: SlotId): Promise<AvailabilitySlot | null>;
  update(slot: AvailabilitySlot): Promise<void>;
}
