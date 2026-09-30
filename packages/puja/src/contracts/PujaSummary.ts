import { PujaId } from '../domain/PujaId';
import { PujaCategory } from '../domain/PujaCategory';

export interface PujaSummary {
  id: PujaId;
  name: string;
  category: PujaCategory;
  duration: number;
}
