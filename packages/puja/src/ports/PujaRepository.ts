import { Puja } from '@navdhya/puja/src/domain/Puja';
import { PujaId } from '@navdhya/puja/src/domain/PujaId';
import { PujaCategory } from '@navdhya/puja/src/domain/PujaCategory';

export interface PujaRepository {
  findById(id: PujaId): Promise<Puja | null>;
  findAll(): Promise<Puja[]>;
  findByCategory(category: PujaCategory): Promise<Puja[]>;
}
