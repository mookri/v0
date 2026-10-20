import { test, expect } from 'vitest';
import { isIdentityUsable } from '@navdhya/identity/src/domain/identity';
import { isSessionLive } from '@navdhya/identity/src/domain/session';
import { validateHandoff } from '@navdhya/identity/src/domain/handoff';
import { createSession, rotateSession } from '@navdhya/identity/src/application/sessions';
import { MemorySessionStore, MemoryHandoffStore } from '@navdhya/identity/src/adapters/memory';
import { authorize, permissionsForRoles } from '@navdhya/authorization/src/index';
import { validateReturnPath, SESSION_COOKIES } from '@navdhya/security/src/cookies';

const NOW = '2026-09-30T00:00:00.000Z';

test('identity lifecycle gates sessions', () => {
  expect(isIdentityUsable({ id: 'u', status: 'ACTIVE', emailVerified: true, phoneVerified: false, createdAt: NOW, updatedAt: NOW })).toBe(true);
  expect(isIdentityUsable({ id: 'u', status: 'DEACTIVATED', emailVerified: true, phoneVerified: false, createdAt: NOW, updatedAt: NOW })).toBe(false);
});

test('session expiry and revocation', () => {
  expect(isSessionLive({ id: 's', identityId: 'u', audience: 'customer', issuedAt: NOW, expiresAt: '2026-10-01T00:00:00.000Z' }, NOW)).toBe(true);
  expect(isSessionLive({ id: 's', identityId: 'u', audience: 'customer', issuedAt: NOW, expiresAt: '2020-01-01T00:00:00.000Z' }, NOW)).toBe(false);
  expect(
    isSessionLive({ id: 's', identityId: 'u', audience: 'customer', issuedAt: NOW, expiresAt: '2026-10-01T00:00:00.000Z', revokedAt: NOW }, NOW),
  ).toBe(false);
});

test('handoff single-use, expiry, audience binding', () => {
  const base = { id: 'h', subject: 'u', source: 'web' as const, audience: 'customer' as const, codeHash: 'h_x', expiresAt: '2026-10-01T00:00:00.000Z', nonce: 'n' };
  expect(validateHandoff(base, { codeHash: 'h_x', audience: 'customer', nowIso: NOW }).ok).toBe(true);
  expect(validateHandoff({ ...base, usedAt: NOW }, { codeHash: 'h_x', audience: 'customer', nowIso: NOW })).toEqual({
    ok: false, error: 'HANDOFF_REPLAYED',
  });
  expect(validateHandoff({ ...base, expiresAt: '2020-01-01T00:00:00.000Z' }, { codeHash: 'h_x', audience: 'customer', nowIso: NOW })).toEqual({
    ok: false, error: 'HANDOFF_EXPIRED',
  });
  expect(validateHandoff(base, { codeHash: 'h_x', audience: 'partner', nowIso: NOW })).toEqual({
    ok: false, error: 'INVALID_AUDIENCE',
  });
});

test('session create + rotate revokes old identifier', async () => {
  const sessions = new MemorySessionStore();
  const identities = { findById: async () => ({ id: 'u', status: 'ACTIVE' as const, emailVerified: true, phoneVerified: false, createdAt: NOW, updatedAt: NOW }), save: async () => {} };
  const s = await createSession({ identities, sessions }, { identityId: 'u', audience: 'customer', ttlSeconds: 3600, nowIso: NOW, sessionId: 's1' });
  const next = await rotateSession({ sessions }, { oldId: 's1', nextId: 's2', nowIso: NOW, ttlSeconds: 3600 });
  expect(next.rotatedFrom).toBe('s1');
  expect((await sessions.get('s1'))?.revokedAt).toBe(NOW);
  expect(s.id).toBe('s1');
});

test('handoff store consumes atomically (no double exchange)', async () => {
  const store = new MemoryHandoffStore();
  await store.save({ codeHash: 'h_x', expiresAt: '2026-10-01T00:00:00.000Z' });
  expect(await store.consume('h_x', NOW)).not.toBeNull();
  expect(await store.consume('h_x', NOW)).toBeNull();
});

test('authorization is product-bound with ownership', () => {
  expect(permissionsForRoles(['customer'])).toContain('booking.cancel');
  const actor = { userId: 'u1', product: 'customer' as const, sessionId: 's', roles: ['customer'] };
  expect(authorize({ product: 'customer', actor, action: 'booking.cancel', resource: { ownerId: 'u1' } })).toEqual({ allowed: true });
  expect(authorize({ product: 'customer', actor, action: 'booking.cancel', resource: { ownerId: 'u2' } })).toEqual({ allowed: false, reason: 'NOT_OWNED' });
  expect(authorize({ product: 'admin', actor, action: 'booking.refund' })).toEqual({ allowed: false, reason: 'PRODUCT_MISMATCH' });
});

test('return paths allowlist internal routes only', () => {
  expect(validateReturnPath('/bookings/123')).toBe('/bookings/123');
  expect(validateReturnPath('https://evil.com')).toBeNull();
  expect(validateReturnPath('//evil.com')).toBeNull();
  expect(validateReturnPath('javascript:alert(1)')).toBeNull();
});

test('session cookies are host-scoped per product', () => {
  expect(SESSION_COOKIES.customer).toBe('__Host-nv_customer');
  expect(SESSION_COOKIES.web).not.toBe(SESSION_COOKIES.customer);
});
