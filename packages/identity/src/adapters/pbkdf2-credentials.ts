import { pbkdf2Sync, randomBytes, timingSafeEqual } from 'node:crypto';
import { CredentialVerifier } from '@navdhya/identity/src/ports/stores';

// Production-grade credential verifier: PBKDF2-SHA256 with per-identity
// salt, server-side pepper, and tunable iterations. No native deps
// (argon2/bcrypt need toolchains); parameters upgrade without re-enrollment
// pressure by versioning the stored record.
export interface PasswordRecord {
  saltHex: string;
  iterations: number;
  hashHex: string;
  version: 1;
}

export const DEFAULT_PBKDF2_ITERATIONS = 210_000;

export function hashPassword(pepper: string, password: string, iterations = DEFAULT_PBKDF2_ITERATIONS): PasswordRecord {
  if (!pepper) throw new Error('PASSWORD_PEPPER_MISSING');
  if (!password) throw new Error('PASSWORD_EMPTY');
  const saltHex = randomBytes(16).toString('hex');
  const hashHex = pbkdf2Sync(`${pepper}:${password}`, Buffer.from(saltHex, 'hex'), iterations, 32, 'sha256').toString('hex');
  return { saltHex, iterations, hashHex, version: 1 };
}

export function verifyPassword(pepper: string, password: string, record: PasswordRecord): boolean {
  if (!pepper || !password) return false;
  const candidate = pbkdf2Sync(`${pepper}:${password}`, Buffer.from(record.saltHex, 'hex'), record.iterations, 32, 'sha256');
  const expected = Buffer.from(record.hashHex, 'hex');
  if (candidate.length !== expected.length) return false;
  return timingSafeEqual(candidate, expected);
}

export class Pbkdf2CredentialVerifier implements CredentialVerifier {
  private readonly records = new Map<string, PasswordRecord>();

  constructor(private readonly pepper: string) {
    if (!pepper) throw new Error('PASSWORD_PEPPER_MISSING');
  }

  enroll(identityId: string, password: string, iterations = DEFAULT_PBKDF2_ITERATIONS): void {
    this.records.set(identityId, hashPassword(this.pepper, password, iterations));
  }

  async verify(identityId: string, proof: unknown): Promise<boolean> {
    const record = this.records.get(identityId);
    if (!record || typeof proof !== 'string') return false;
    return verifyPassword(this.pepper, proof, record);
  }
}
