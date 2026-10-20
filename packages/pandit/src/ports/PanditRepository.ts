import { Pandit } from '@navdhya/pandit/src/domain/Pandit';
import { PanditId } from '@navdhya/pandit/src/domain/PanditId';

export interface PanditRepository {
  findById(id: PanditId): Promise<Pandit | null>;
  findByUserId(userId: string): Promise<Pandit | null>;
  search(query: string): Promise<Pandit[]>;
  update(pandit: Pandit): Promise<void>;
}
