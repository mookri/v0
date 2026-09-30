import type { Puja } from '../../../../packages/puja/src/domain/Puja';
import type { PujaId } from '../../../../packages/puja/src/domain/PujaId';
import type { PujaCategory } from '../../../../packages/puja/src/domain/PujaCategory';
import type { PujaRepository } from '../../../../packages/puja/src/ports/PujaRepository';
import type { DbClient } from '../client';

// Owns: pujas, categories, rituals. Backed by static catalog until Postgres
// tables are provisioned; the port stays stable either way.
export class PostgresPujaRepository implements PujaRepository {
  private readonly store = new Map<PujaId, Puja>();

  constructor(private readonly _db: DbClient) {}

  seed(pujas: Puja[]): void {
    for (const p of pujas) this.store.set(p.id, p);
  }

  async findById(id: PujaId): Promise<Puja | null> {
    return this.store.get(id) ?? null;
  }

  async findAll(): Promise<Puja[]> {
    return [...this.store.values()];
  }

  async findByCategory(category: PujaCategory): Promise<Puja[]> {
    return [...this.store.values()].filter((p) => p.category === category);
  }
}
