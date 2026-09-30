import { PujaCategory } from '@navdhya/puja/src/domain/PujaCategory';

export async function getPujaCategories(): Promise<PujaCategory[]> {
  return ['FESTIVAL', 'CEREMONY', 'DAILY', 'SPECIAL'];
}
