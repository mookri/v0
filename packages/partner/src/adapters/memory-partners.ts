import { Partner } from '@navdhya/partner/src/domain/partner';
import { PartnerRepository } from '@navdhya/partner/src/application/lifecycle';

export class MemoryPartnerRepository implements PartnerRepository {
  private readonly byId = new Map<string, Partner>();

  async findById(id: string): Promise<Partner | null> {
    const found = this.byId.get(id);
    return found ? { ...found } : null;
  }

  async findByIdentity(identityId: string): Promise<Partner[]> {
    return [...this.byId.values()].filter((p) => p.identityId === identityId).map((p) => ({ ...p }));
  }

  async save(partner: Partner): Promise<void> {
    if (this.byId.has(partner.id)) throw new Error(`PARTNER_EXISTS:${partner.id}`);
    this.byId.set(partner.id, { ...partner });
  }

  async update(partner: Partner): Promise<void> {
    if (!this.byId.has(partner.id)) throw new Error(`PARTNER_NOT_FOUND:${partner.id}`);
    this.byId.set(partner.id, { ...partner });
  }
}
