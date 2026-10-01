import {
  PartnerApplication,
  canSubmitApplication,
  assertApplicationTransition,
} from '@navdhya/partner/src/domain/partner-application';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps, DraftEvidence } from '@navdhya/partner/src/onboarding/steps';
import { ChallengePort, ChallengePolicy } from '@navdhya/partner/src/ports/challenges';

// Submit OTP policy (versioned numbers, not code constants scattered around).
export const SUBMIT_OTP_POLICY: ChallengePolicy = {
  codeLength: 6,
  ttlSeconds: 600,
  maxAttempts: 3,
  maxChallengesPerHour: 5,
};

// Phase 1: validate the draft against the pinned track version, then issue
// the OTP challenge. The application is NOT transitioned here.
export async function beginSubmit(
  deps: { otp: ChallengePort },
  app: PartnerApplication,
  evidence: DraftEvidence,
  identifier: string,
  nowIso: string,
): Promise<{ application: PartnerApplication; challengeId: string }> {
  if (!canSubmitApplication(app.status)) throw new Error(`SUBMIT_INVALID_STATE:${app.status}`);
  const def = getTrackDefinition(app.track, app.trackVersion);
  const missing = missingSteps(def, evidence);
  if (missing.length > 0) throw new Error(`SUBMIT_INCOMPLETE:${missing.join(',')}`);
  const { challengeId } = await deps.otp.requestChallenge(identifier, SUBMIT_OTP_POLICY, nowIso);
  return { application: app, challengeId };
}

// Phase 2: re-validate (edits between phases can't sneak through), verify
// the challenge, then transition. Consumed/expired/exhausted challenges all
// collapse to one code (no oracle).
export async function confirmSubmit(
  deps: { otp: ChallengePort },
  app: PartnerApplication,
  evidence: DraftEvidence,
  challengeId: string,
  code: string,
  nowIso: string,
): Promise<{ application: PartnerApplication }> {
  if (!canSubmitApplication(app.status)) throw new Error(`SUBMIT_INVALID_STATE:${app.status}`);
  const def = getTrackDefinition(app.track, app.trackVersion);
  const missing = missingSteps(def, evidence);
  if (missing.length > 0) throw new Error(`SUBMIT_INCOMPLETE:${missing.join(',')}`);
  const verified = await deps.otp.verifyChallenge(challengeId, code, nowIso);
  if (!verified) throw new Error('OTP_INVALID');
  assertApplicationTransition(app.status, 'SUBMITTED');
  return { application: { ...app, status: 'SUBMITTED', submittedAt: nowIso } };
}
