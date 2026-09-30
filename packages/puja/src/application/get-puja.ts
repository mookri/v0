import { PujaRepository } from '@navdhya/puja/src/ports/PujaRepository';
import { PujaId } from '@navdhya/puja/src/domain/PujaId';
import { Puja } from '@navdhya/puja/src/domain/Puja';

export async function getPuja(repo: PujaRepository, id: PujaId): Promise<Puja | null> {
  return repo.findById(id);
}
