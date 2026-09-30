import { PanditRepository } from '../ports/PanditRepository';
import { PanditId } from '../domain/PanditId';
import { PanditDetails } from '../contracts/PanditDetails';

export async function getPanditProfile(repo: PanditRepository, id: PanditId): Promise<PanditDetails | null> {
  const pandit = await repo.findById(id);
  return pandit ? { pandit } : null;
}
