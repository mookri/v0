import { AvailabilityRepository } from '../ports/AvailabilityRepository';
import { SlotId } from '../domain/SlotId';
import { isSlotAvailable } from '../domain/availability-rules';

export async function reserveSlot(repo: AvailabilityRepository, id: SlotId): Promise<void> {
  const slot = await repo.findById(id);
  if (!slot) throw new Error('Slot not found');
  if (!isSlotAvailable(slot)) throw new Error('Slot is not available');
  slot.isReserved = true;
  await repo.update(slot);
}
