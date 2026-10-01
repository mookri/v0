import { PartnerApplication } from '@navdhya/partner/src/domain/partner-application';

// Application persistence port. Memory adapter for dev/staging; shared
// store later behind this interface — callers (routes, workers) never change.
export interface OnboardingRepository {
  save(app: PartnerApplication): Promise<void>;
  findById(id: string): Promise<PartnerApplication | null>;
  findByIdentity(identityId: string): Promise<PartnerApplication[]>;
  update(app: PartnerApplication): Promise<void>;
}
