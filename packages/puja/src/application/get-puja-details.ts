import { PujaRepository } from '../ports/PujaRepository';
import { PujaId } from '../domain/PujaId';
import { PujaDetails } from '../contracts/PujaDetails';

export async function getPujaDetails(repo: PujaRepository, id: PujaId): Promise<PujaDetails | null> {
  const puja = await repo.findById(id);
  return puja ? { puja } : null;
}
