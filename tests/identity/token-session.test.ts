import { test, expect } from 'vitest';
import { login, logout, resolveSessionToken } from '@navdhya/identity/src/application/token-session';
import { MemorySessionStore } from '@navdhya/identity/src/adapters/memory';
import { INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';
import { Identity } from '@navdhya/identity/src/domain/identity';

const NOW = '2026-10-01T00:00:00.000Z';
const identity: Identity = {
  id: 'u1',
  status: 'ACTIVE',
  emailVerified: true,
  phoneVerified: false,
  createdAt: NOW,
  updatedAt: NOW,
};

const deps = (over: Record<string, unknown> = {}) => ({
  identities: { findById: async (id: string) => (id === 'u1' ? identity : null), save: async () => {} },
  sessions: new MemorySessionStore(),
  verifier: { verify: async () => true },
  tokenSecret: INSECURE_DEV_SECRET,
  ...over,
});

const input = (over: Record<string, unknown> = {}) => ({
  identityId: 'u1',
  proof: 'pw',
  audience: 'customer' as const,
  roles: ['customer'],
  sessionId: 'sess_1',
  tokenTtlSeconds: 3600,
  sessionTtlSeconds: 7200,
  nowIso: NOW,
  ...over,
});

test('login mints a session-bound token; logout kills it', async () => {
  const d = deps();
  const { session, token } = await login(d, input());
  expect(session.id).toBe('sess_1');
  const actor = await resolveSessionToken(d, token, NOW);
  expect(actor).toEqual({ userId: 'u1', sessionId: 'sess_1', roles: ['customer'] });
  await logout(d, 'sess_1', NOW);
  await expect(resolveSessionToken(d, token, NOW)).rejects.toThrow('SESSION_REVOKED');
});

test('login rejects inactive identities and bad credentials', async () => {
  const d = deps();
  await expect(login(d, input({ identityId: 'ghost' }))).rejects.toThrow('IDENTITY_INACTIVE');
  const badCreds = deps({ verifier: { verify: async () => false } });
  await expect(login(badCreds, input({ sessionId: 'sess_x' }))).rejects.toThrow('CREDENTIAL_INVALID');
  const suspended = deps({
    identities: { findById: async () => ({ ...identity, status: 'SUSPENDED' as const }), save: async () => {} },
  });
  await expect(login(suspended, input({ sessionId: 'sess_y' }))).rejects.toThrow('IDENTITY_INACTIVE');
});

test('tokens for unknown or expired sessions reject', async () => {
  const d = deps();
  const { token } = await login(d, input({ sessionId: 'sess_e', tokenTtlSeconds: 7200, sessionTtlSeconds: 1800 }));
  const empty: typeof d = { ...d, sessions: new MemorySessionStore() };
  await expect(resolveSessionToken(empty, token, NOW)).rejects.toThrow('SESSION_UNKNOWN');
  // Token valid for 2h, session for 30m: session layer fires first.
  await expect(resolveSessionToken(d, token, '2026-10-01T01:00:00.000Z')).rejects.toThrow('SESSION_EXPIRED');
  // Past both TTLs the token layer fires first.
  await expect(resolveSessionToken(d, token, '2026-10-01T03:00:00.000Z')).rejects.toThrow('ACTOR_TOKEN_EXPIRED');
});
