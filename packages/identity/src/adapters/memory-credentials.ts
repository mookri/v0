import { timingSafeEqual } from 'node:crypto';
import { CredentialVerifier } from '@navdhya/identity/src/ports/stores';

// Dev/staging only: plaintext proofs in process memory. Production uses a
// password-hash verifier behind the same port (argon2/bcrypt) — never this.
export class MemoryCredentialVerifier implements CredentialVerifier {
  private readonly secrets = new Map<string, string>();

  seed(identityId: string, proof: string): void {
    this.secrets.set(identityId, proof);
  }

  async verify(identityId: string, proof: unknown): Promise<boolean> {
    const expected = this.secrets.get(identityId);
    if (!expected || typeof proof !== 'string' || !proof) return false;
    const a = Buffer.from(expected, 'utf8');
    const b = Buffer.from(proof, 'utf8');
    if (a.length !== b.length) return false;
    return timingSafeEqual(a, b);
  }
}
