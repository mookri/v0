import { MemoryIdentityRepository, MemorySessionStore } from '@navdhya/identity/src/adapters/memory';
import { MemoryCredentialVerifier } from '@navdhya/identity/src/adapters/memory-credentials';
import { login } from '@navdhya/identity/src/application/token-session';
import { resolveActorTokenSecret } from '@navdhya/identity/src/application/token-actor';

// Partner auth composition mirrors the customer one (single-instance
// staging stores; shared backends swap behind ports).
export const partnerIdentities = new MemoryIdentityRepository();
export const partnerSessions = new MemorySessionStore();
export const partnerVerifier = new MemoryCredentialVerifier();

// Throws SESSION_SIGNING_KEY_MISSING in production when the key is unset;
// the dev fallback is deliberately restricted to non-production.
export function partnerTokenSecret(): string {
  return resolveActorTokenSecret();
}

export const SESSION_COOKIE = '__session';

// Test-only helper: mints a live session + token for a user.
export async function __issuePartnerTestToken(userId: string, roles: string[] = ['partner'], market?: string): Promise<string> {
  const nowIso = new Date().toISOString();
  await partnerIdentities.save({
    id: userId,
    status: 'ACTIVE',
    emailVerified: false,
    phoneVerified: false,
    createdAt: nowIso,
    updatedAt: nowIso,
  });
  partnerVerifier.seed(userId, 'test-proof');
  const { token } = await login(
    { identities: partnerIdentities, sessions: partnerSessions, verifier: partnerVerifier, tokenSecret: partnerTokenSecret() },
    {
      identityId: userId,
      proof: 'test-proof',
      audience: 'partner',
      roles,
      ...(market ? { market } : {}),
      sessionId: `sess_test_${userId}_${Date.now()}`,
      tokenTtlSeconds: 3600,
      sessionTtlSeconds: 86400,
      nowIso,
    },
  );
  return token;
}
