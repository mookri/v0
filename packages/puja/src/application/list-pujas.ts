import { PujaRepository } from '@navdhya/puja/src/ports/PujaRepository';
import { Puja } from '@navdhya/puja/src/domain/Puja';

export async function listPujas(repo: PujaRepository): Promise<Puja[]> {
  return repo.findAll();
}
