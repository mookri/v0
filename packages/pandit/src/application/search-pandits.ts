import { PanditRepository } from '@navdhya/pandit/src/ports/PanditRepository';
import { Pandit } from '@navdhya/pandit/src/domain/Pandit';

export async function searchPandits(repo: PanditRepository, query: string): Promise<Pandit[]> {
  return repo.search(query);
}
