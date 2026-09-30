import { PanditRepository } from '../ports/PanditRepository';
import { Pandit } from '../domain/Pandit';

export async function searchPandits(repo: PanditRepository, query: string): Promise<Pandit[]> {
  return repo.search(query);
}
