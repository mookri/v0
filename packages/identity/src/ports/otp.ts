// OTP challenge port (L1 proofing). Memory/fake adapter for dev/staging
// (deterministic in tests, logged-not-sent); licensed SMS/voice vendor
// adapter later behind this same port. Provider capability flags (voice
// fallback) are data, not code branches.

export interface OtpChallengePolicy {
  codeLength: number;
  ttlSeconds: number;
  maxAttempts: number;
  maxChallengesPerHour: number;
}

export interface OtpChallenge {
  id: string;
  identifier: string;
  codeHash: string;
  expiresAtIso: string;
  attempts: number;
  createdAtIso: string;
}

export interface OtpProvider {
  requestChallenge(identifier: string, policy: OtpChallengePolicy, nowIso: string): Promise<{ challengeId: string }>;
  verifyChallenge(challengeId: string, code: string, nowIso: string): Promise<boolean>;
}
