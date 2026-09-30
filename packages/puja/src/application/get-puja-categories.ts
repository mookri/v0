import { PujaCategory } from '../domain/PujaCategory';

export async function getPujaCategories(): Promise<PujaCategory[]> {
  return ['FESTIVAL', 'CEREMONY', 'DAILY', 'SPECIAL'];
}
