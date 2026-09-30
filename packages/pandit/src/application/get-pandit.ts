import { PanditRepository } from '../ports/PanditRepository';
import { PanditId } from '../domain/PanditId';
import { Pandit } from '../domain/Pandit';

export async function getPandit(repo: PanditRepository, id: PanditId): Promise<Pandit | null> {
  return repo.findById(id);
}
