import { PanditRepository } from '@navdhya/pandit/src/ports/PanditRepository';
import { PanditId } from '@navdhya/pandit/src/domain/PanditId';
import { Pandit } from '@navdhya/pandit/src/domain/Pandit';

export async function getPandit(repo: PanditRepository, id: PanditId): Promise<Pandit | null> {
  return repo.findById(id);
}
