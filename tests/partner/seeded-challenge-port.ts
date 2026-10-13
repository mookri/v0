import { ChallengePort, ChallengePolicy } from '@navdhya/partner/src/ports/challenges';

// Deterministic in-memory ChallengePort for root tests. Replaces the deleted
// identity SeededOtpProvider (OTP moved out of @navdhya/identity in the MSG91
// migration); partner's ChallengePort is satisfied structurally per its
// dependency-inversion comment. Codes derive from the challenge sequence so
// tests can compute them via codeFor(challengeId).
export class SeededChallengePort implements ChallengePort {
  private readonly challenges = new Map<
    string,
    { code: string; expiresAtIso: string; attempts: number; maxAttempts: number }
  >();
  private readonly issuedPerHour = new Map<string, number>();
  private seq = 0;

  async requestChallenge(
    identifier: string,
    policy: ChallengePolicy,
    nowIso: string,
  ): Promise<{ challengeId: string }> {
    const hour = nowIso.slice(0, 13);
    const key = `${identifier}:${hour}`;
    const count = this.issuedPerHour.get(key) ?? 0;
    if (count >= policy.maxChallengesPerHour) throw new Error('OTP_RATE_LIMITED');
    this.issuedPerHour.set(key, count + 1);
    this.seq += 1;
    const challengeId = `ch_${this.seq}`;
    this.challenges.set(challengeId, {
      code: this.generateCode(policy.codeLength, challengeId),
      expiresAtIso: new Date(Date.parse(nowIso) + policy.ttlSeconds * 1000).toISOString(),
      attempts: 0,
      maxAttempts: policy.maxAttempts,
    });
    return { challengeId };
  }

  async verifyChallenge(challengeId: string, token: string, nowIso?: string): Promise<boolean> {
    const record = this.challenges.get(challengeId);
    if (!record) return false;
    const at = nowIso ?? new Date().toISOString();
    if (record.expiresAtIso <= at || record.attempts >= record.maxAttempts) {
      this.challenges.delete(challengeId);
      return false;
    }
    record.attempts += 1;
    const ok = record.code === token;
    if (ok) this.challenges.delete(challengeId);
    return ok;
  }

  codeFor(challengeId: string, length = 6): string {
    const n = parseInt(challengeId.replace('ch_', ''), 10) || 0;
    return String(100000 + (n % 900000)).slice(0, length).padStart(length, '0');
  }

  private generateCode(length: number, challengeId: string): string {
    const n = parseInt(challengeId.replace('ch_', ''), 10) || 0;
    return String(100000 + (n % 900000)).slice(0, length).padStart(length, '0');
  }
}
