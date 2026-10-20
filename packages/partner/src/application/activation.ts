import { Partner, PartnerType, PartnerStatus } from '@navdhya/partner/src/domain/partner';
import { PartnerRepository, canTransitionPartner } from '@navdhya/partner/src/application/lifecycle';
import { isPartnerWorkEligible } from '@navdhya/partner/src/domain/partner';

// Activation: approval promotes the applicant to a work-eligible partner.
// Existing records advance VERIFIED→ACTIVE (or stay when already ACTIVE);
// missing records are created ACTIVE. Every other status is left untouched
// and reported — activation never force-transitions suspensions or exits.
export interface ActivationResult {
  partner: Partner;
  created: boolean;
  eligible: boolean;
}

export async function activateOnApproval(
  deps: { partners: PartnerRepository },
  input: { identityId: string; type: PartnerType; partnerId: string; nowIso: string },
): Promise<ActivationResult> {
  void input.nowIso;
  const existing = await deps.partners.findByIdentity(input.identityId);
  const match = existing.find((p) => p.type === input.type) ?? null;
  if (!match) {
    const partner: Partner = {
      id: input.partnerId,
      identityId: input.identityId,
      type: input.type,
      lifecycleStatus: 'ACTIVE',
      agreementVersion: 1,
      capabilityVersion: 1,
    };
    await deps.partners.save(partner);
    return { partner, created: true, eligible: true };
  }
  const next: PartnerStatus = match.lifecycleStatus === 'VERIFIED' ? 'ACTIVE' : match.lifecycleStatus;
  if (next === match.lifecycleStatus) {
    return { partner: match, created: false, eligible: isPartnerWorkEligible(next) };
  }
  if (!canTransitionPartner(match.lifecycleStatus, next)) {
    return { partner: match, created: false, eligible: false };
  }
  const updated: Partner = { ...match, lifecycleStatus: next };
  await deps.partners.update(updated);
  return { partner: updated, created: false, eligible: isPartnerWorkEligible(next) };
}
