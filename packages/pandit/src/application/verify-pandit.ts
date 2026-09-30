import { PanditRepository } from '@navdhya/pandit/src/ports/PanditRepository';
import { Pandit } from '@navdhya/pandit/src/domain/Pandit';

export async function verifyPandit(repo: PanditRepository, pandit: Pandit): Promise<void> {
  pandit.status = 'VERIFIED';
  await repo.update(pandit);
}
