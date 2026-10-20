import { AvailabilityRepository } from '@navdhya/availability/src/ports/AvailabilityRepository';
import { SlotId } from '@navdhya/availability/src/domain/SlotId';

export async function releaseSlot(repo: AvailabilityRepository, id: SlotId): Promise<void> {
  const slot = await repo.findById(id);
  if (!slot) throw new Error('Slot not found');
  slot.isReserved = false;
  await repo.update(slot);
}
