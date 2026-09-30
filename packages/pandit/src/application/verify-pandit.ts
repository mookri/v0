import { PanditRepository } from '../ports/PanditRepository';
import { Pandit } from '../domain/Pandit';

export async function verifyPandit(repo: PanditRepository, pandit: Pandit): Promise<void> {
  pandit.status = 'VERIFIED';
  await repo.update(pandit);
}
