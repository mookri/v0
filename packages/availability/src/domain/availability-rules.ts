import { AvailabilitySlot } from './AvailabilitySlot';

export function isSlotAvailable(slot: AvailabilitySlot): boolean {
  return !slot.isReserved && slot.startTime > new Date();
}
