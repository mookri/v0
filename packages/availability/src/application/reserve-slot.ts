import { AvailabilityRepository } from '@navdhya/availability/src/ports/AvailabilityRepository';
import { SlotId } from '@navdhya/availability/src/domain/SlotId';
import { isSlotAvailable } from '@navdhya/availability/src/domain/availability-rules';

export async function reserveSlot(repo: AvailabilityRepository, id: SlotId): Promise<void> {
  const slot = await repo.findById(id);
  if (!slot) throw new Error('Slot not found');
  if (!isSlotAvailable(slot)) throw new Error('Slot is not available');
  slot.isReserved = true;
  await repo.update(slot);
}
