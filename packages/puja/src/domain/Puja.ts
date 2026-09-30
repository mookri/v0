import { PujaId } from './PujaId';
import { PujaCategory } from './PujaCategory';
import { PujaType } from './PujaType';
import { Ritual } from './Ritual';

export interface Puja {
  id: PujaId;
  name: string;
  description: string;
  category: PujaCategory;
  type: PujaType;
  rituals: Ritual[];
  baseDurationMinutes: number;
  isActive: boolean;
}
