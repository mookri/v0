import { MemoryOnboardingRepository } from '@navdhya/partner/src/adapters/memory-applications';
import { MemoryDocumentStore, MemoryUploadUrls } from '@navdhya/partner/src/adapters/memory-documents';
import { MemoryOtpProvider } from '@navdhya/identity/src/adapters/memory-otp';
import { partnerActorResolver } from '@/application/actor';
import { DraftEvidence } from '@navdhya/partner/src/onboarding/steps';

// Onboarding composition (single-instance staging stores; shared backends
// swap behind ports without touching routes).
export const onboardingApps = new MemoryOnboardingRepository();
export const onboardingDocs = new MemoryDocumentStore();
export const onboardingUploads = new MemoryUploadUrls();

let otp: MemoryOtpProvider = new MemoryOtpProvider();
export function onboardingOtp(): MemoryOtpProvider {
  return otp;
}

export const onboardingActors = partnerActorResolver();

// Draft evidence lives here until the repository owns it (stage 6:
// evidence column on the application record).
const evidence = new Map<string, DraftEvidence>();
export function getEvidence(applicationId: string): DraftEvidence {
  return evidence.get(applicationId) ?? {};
}
export function saveEvidence(applicationId: string, next: DraftEvidence): void {
  evidence.set(applicationId, next);
}

export function __useOtpForTests(provider: MemoryOtpProvider): void {
  otp = provider;
}
export function __resetEvidenceForTests(): void {
  evidence.clear();
}
