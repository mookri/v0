import { MemoryOnboardingRepository } from '@navdhya/partner/src/adapters/memory-applications';
import { MemoryDocumentStore, MemoryUploadUrls } from '@navdhya/partner/src/adapters/memory-documents';
import { MemoryOtpProvider } from '@navdhya/identity/src/adapters/memory-otp';
import { partnerActorResolver, headersToRecord, readSessionCookie } from '@/application/actor';
import { partnerSessions, partnerTokenSecret } from '@/server/auth';
import { resolveSessionToken } from '@navdhya/identity/src/application/token-session';
import { err } from '@/application/api-envelope';
import { assertSameTenant } from '@navdhya/identity/src/ports/actor-resolver';
import { PartnerApplication } from '@navdhya/partner/src/domain/partner-application';
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

// Shared applicant guard: auth + existence + ownership + tenant scope.
// All applicant routes use this — never inline compares.
export async function loadOwnApplication(
  req: Request,
  requestId: string,
  applicationId: string,
): Promise<{ application: PartnerApplication } | { response: Response }> {
  const headers = headersToRecord(req.headers);
  if (!headers['authorization']) {
    const cookieToken = readSessionCookie(req.headers.get('cookie'));
    if (cookieToken) headers['authorization'] = `Bearer ${cookieToken}`;
  }
  const bearer = headers['authorization'];
  if (!bearer?.startsWith('Bearer ')) {
    return { response: Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 }) };
  }
  // Session-bound: revoked/expired sessions reject even with valid signatures.
  let actor;
  try {
    actor = await resolveSessionToken(
      { sessions: partnerSessions, tokenSecret: partnerTokenSecret() },
      bearer.slice('Bearer '.length).trim(),
      new Date().toISOString(),
    );
  } catch {
    return { response: Response.json(err('UNAUTHENTICATED', 'Invalid session.', requestId), { status: 401 }) };
  }
  const application = await onboardingApps.findById(applicationId);
  if (!application) {
    return { response: Response.json(err('ONBOARDING_NOT_FOUND', 'Application not found.', requestId), { status: 404 }) };
  }
  if (application.identityId !== actor.userId) {
    return { response: Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 }) };
  }
  try {
    assertSameTenant(actor, { market: application.market });
  } catch {
    return { response: Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 }) };
  }
  return { application };
}
