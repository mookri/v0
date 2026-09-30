import { Puja } from '../domain/Puja';
import { PujaId } from '../domain/PujaId';
import { PujaCategory } from '../domain/PujaCategory';

export interface PujaRepository {
  findById(id: PujaId): Promise<Puja | null>;
  findAll(): Promise<Puja[]>;
  findByCategory(category: PujaCategory): Promise<Puja[]>;
}
