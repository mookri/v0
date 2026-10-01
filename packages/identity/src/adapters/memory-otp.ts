import { createHash, timingSafeEqual } from 'node:crypto';
import { OtpChallengePolicy, OtpProvider } from '@navdhya/identity/src/ports/otp';

function hash(code: string): string {
  return createHash('sha256').update(code, 'utf8').digest('hex');
}

function randomCode(length: number): string {
  let out = '';
  for (let i = 0; i < length; i++) out += Math.floor(Math.random() * 10).toString();
  return out;
}

// Dev/staging adapter: codes kept in memory (test hook reads them back).
// Production uses a vendor adapter; callers never change.
export class MemoryOtpProvider implements OtpProvider {
  private readonly challenges = new Map<
    string,
    { identifier: string; codeHash: string; expiresAtIso: string; attempts: number; maxAttempts: number }
  >();
  private readonly issuedPerHour = new Map<string, number>();
  private seq = 0;

  protected generateCode(length: number, challengeId: string): string {
    void challengeId;
    return randomCode(length);
  }

  async requestChallenge(identifier: string, policy: OtpChallengePolicy, nowIso: string): Promise<{ challengeId: string }> {
    const hour = nowIso.slice(0, 13);
    const key = `${identifier}:${hour}`;
    const count = this.issuedPerHour.get(key) ?? 0;
    if (count >= policy.maxChallengesPerHour) throw new Error('OTP_RATE_LIMITED');
    this.issuedPerHour.set(key, count + 1);
    this.seq += 1;
    const challengeId = `ch_${this.seq}`;
    const code = this.generateCode(policy.codeLength, challengeId);
    this.challenges.set(challengeId, {
      identifier,
      codeHash: hash(code),
      expiresAtIso: new Date(Date.parse(nowIso) + policy.ttlSeconds * 1000).toISOString(),
      attempts: 0,
      maxAttempts: policy.maxAttempts,
    });
    return { challengeId };
  }

  async verifyChallenge(challengeId: string, code: string, nowIso: string): Promise<boolean> {
    const record = this.challenges.get(challengeId);
    if (!record) return false;
    if (record.expiresAtIso <= nowIso || record.attempts >= record.maxAttempts) {
      this.challenges.delete(challengeId);
      return false;
    }
    record.attempts += 1;
    const a = Buffer.from(record.codeHash, 'utf8');
    const b = Buffer.from(hash(code), 'utf8');
    const ok = a.length === b.length && timingSafeEqual(a, b);
    if (ok) this.challenges.delete(challengeId);
    return ok;
  }
}

// Deterministic subclass for tests: codes derived from challenge id.
export class SeededOtpProvider extends MemoryOtpProvider {
  protected generateCode(length: number, challengeId: string): string {
    const n = parseInt(challengeId.replace('ch_', ''), 10) || 0;
    return String(100000 + (n % 900000)).slice(0, length).padStart(length, '0');
  }

  codeFor(challengeId: string, length = 6): string {
    return this.generateCode(length, challengeId);
  }
}
