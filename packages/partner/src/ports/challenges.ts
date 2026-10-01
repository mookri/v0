// Challenge port owned by the partner capability (dependency inversion:
// the capability defines what it needs; identity's OTP adapters satisfy it
// structurally — see MemoryOtpProvider. No package-to-package import.)

export interface ChallengePolicy {
  codeLength: number;
  ttlSeconds: number;
  maxAttempts: number;
  maxChallengesPerHour: number;
}

export interface ChallengePort {
  requestChallenge(identifier: string, policy: ChallengePolicy, nowIso: string): Promise<{ challengeId: string }>;
  verifyChallenge(challengeId: string, code: string, nowIso: string): Promise<boolean>;
}
