import { Pandit } from '../domain/Pandit';
import { PanditId } from '../domain/PanditId';

export interface PanditRepository {
  findById(id: PanditId): Promise<Pandit | null>;
  findByUserId(userId: string): Promise<Pandit | null>;
  search(query: string): Promise<Pandit[]>;
  update(pandit: Pandit): Promise<void>;
}
