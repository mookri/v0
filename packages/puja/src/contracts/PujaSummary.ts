import { PujaId } from '@navdhya/puja/src/domain/PujaId';
import { PujaCategory } from '@navdhya/puja/src/domain/PujaCategory';

export interface PujaSummary {
  id: PujaId;
  name: string;
  category: PujaCategory;
  duration: number;
}
