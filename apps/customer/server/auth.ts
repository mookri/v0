import { MemoryIdentityRepository, MemorySessionStore } from '@navdhya/identity/src/adapters/memory';
import { MemoryCredentialVerifier } from '@navdhya/identity/src/adapters/memory-credentials';
import { INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';
import { login } from '@navdhya/identity/src/application/token-session';

// Auth composition for customer BFF routes. Single-instance staging stores
// (same limitation as the events memory bus); a shared backend replaces
// these behind the identity ports without touching routes.
export const authIdentities = new MemoryIdentityRepository();
export const authSessions = new MemorySessionStore();
export const authVerifier = new MemoryCredentialVerifier();

export function authTokenSecret(): string {
  return process.env['SESSION_SIGNING_KEY'] ?? INSECURE_DEV_SECRET;
}

// Test-only helper: mints a live session + token for a user.
export async function __issueTestToken(userId: string): Promise<string> {
  const nowIso = new Date().toISOString();
  await authIdentities.save({
    id: userId,
    status: 'ACTIVE',
    emailVerified: false,
    phoneVerified: false,
    createdAt: nowIso,
    updatedAt: nowIso,
  });
  authVerifier.seed(userId, 'test-proof');
  const { token } = await login(
    { identities: authIdentities, sessions: authSessions, verifier: authVerifier, tokenSecret: authTokenSecret() },
    {
      identityId: userId,
      proof: 'test-proof',
      audience: 'customer',
      roles: ['customer'],
      sessionId: `sess_test_${userId}_${Date.now()}`,
      tokenTtlSeconds: 3600,
      sessionTtlSeconds: 86400,
      nowIso,
    },
  );
  return token;
}

// Explicit opt-in dev seed (staging only): SEED_DEV_IDENTITY=1 creates
// dev-user with proof from SEED_DEV_PROOF (default 'dev-proof', insecure).
// Never set in production.
export function seedDevIdentity(): void {
  if (process.env['SEED_DEV_IDENTITY'] !== '1') return;
  const nowIso = new Date().toISOString();
  void authIdentities.save({
    id: 'dev-user',
    status: 'ACTIVE',
    emailVerified: false,
    phoneVerified: false,
    createdAt: nowIso,
    updatedAt: nowIso,
  });
  authVerifier.seed('dev-user', process.env['SEED_DEV_PROOF'] ?? 'dev-proof');
}
