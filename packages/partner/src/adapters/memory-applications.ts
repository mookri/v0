import { PartnerApplication } from '@navdhya/partner/src/domain/partner-application';
import { OnboardingRepository } from '@navdhya/partner/src/ports/applications';

// Single-instance staging adapter (same limitation as the other memory
// adapters); a shared store replaces this behind the port.
export class MemoryOnboardingRepository implements OnboardingRepository {
  private readonly byId = new Map<string, PartnerApplication>();

  async save(app: PartnerApplication): Promise<void> {
    if (this.byId.has(app.id)) throw new Error(`APPLICATION_EXISTS:${app.id}`);
    this.byId.set(app.id, { ...app });
  }

  async findById(id: string): Promise<PartnerApplication | null> {
    const found = this.byId.get(id);
    return found ? { ...found } : null;
  }

  async findByIdentity(identityId: string): Promise<PartnerApplication[]> {
    return [...this.byId.values()].filter((a) => a.identityId === identityId).map((a) => ({ ...a }));
  }

  async update(app: PartnerApplication): Promise<void> {
    if (!this.byId.has(app.id)) throw new Error(`APPLICATION_NOT_FOUND:${app.id}`);
    this.byId.set(app.id, { ...app });
  }
}
