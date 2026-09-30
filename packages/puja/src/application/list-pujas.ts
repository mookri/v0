import { PujaRepository } from '../ports/PujaRepository';
import { Puja } from '../domain/Puja';

export async function listPujas(repo: PujaRepository): Promise<Puja[]> {
  return repo.findAll();
}
