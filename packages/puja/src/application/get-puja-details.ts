import { PujaRepository } from '@navdhya/puja/src/ports/PujaRepository';
import { PujaId } from '@navdhya/puja/src/domain/PujaId';
import { PujaDetails } from '@navdhya/puja/src/contracts/PujaDetails';

export async function getPujaDetails(repo: PujaRepository, id: PujaId): Promise<PujaDetails | null> {
  const puja = await repo.findById(id);
  return puja ? { puja } : null;
}
