import { PujaRepository } from '../ports/PujaRepository';
import { PujaId } from '../domain/PujaId';
import { Puja } from '../domain/Puja';

export async function getPuja(repo: PujaRepository, id: PujaId): Promise<Puja | null> {
  return repo.findById(id);
}
