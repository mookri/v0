import { PanditRepository } from '@navdhya/pandit/src/ports/PanditRepository';
import { PanditId } from '@navdhya/pandit/src/domain/PanditId';
import { PanditDetails } from '@navdhya/pandit/src/contracts/PanditDetails';

export async function getPanditProfile(repo: PanditRepository, id: PanditId): Promise<PanditDetails | null> {
  const pandit = await repo.findById(id);
  return pandit ? { pandit } : null;
}
