import { test, expect } from 'vitest';
import {
  mintActorToken,
  verifyActorToken,
  SignedTokenActorResolver,
  resolveActorTokenSecret,
  INSECURE_DEV_SECRET,
  SESSION_SIGNING_KEY_MISSING,
} from '@navdhya/identity/src/application/token-actor';
import { assertSameTenant } from '@navdhya/identity/src/ports/actor-resolver';

const NOW = '2026-10-01T00:00:00.000Z';
const actor = { userId: 'u1', sessionId: 'sess_1', roles: ['customer'] };

test('token round-trips; tampered and expired tokens rejected', () => {
  const token = mintActorToken(INSECURE_DEV_SECRET, actor, NOW, 3600);
  expect(verifyActorToken(INSECURE_DEV_SECRET, token, NOW)).toEqual(actor);
  expect(() => verifyActorToken(INSECURE_DEV_SECRET, token + 'x', NOW)).toThrow('ACTOR_TOKEN_INVALID');
  expect(() => verifyActorToken('other-secret', token, NOW)).toThrow('ACTOR_TOKEN_INVALID');
  expect(() => verifyActorToken(INSECURE_DEV_SECRET, token, '2026-10-01T02:00:00.000Z')).toThrow('ACTOR_TOKEN_EXPIRED');
  expect(() => verifyActorToken(INSECURE_DEV_SECRET, 'garbage', NOW)).toThrow('ACTOR_TOKEN_MALFORMED');
  expect(() => mintActorToken(INSECURE_DEV_SECRET, { ...actor, userId: 'a.b' }, NOW, 60)).toThrow('ACTOR_FIELD_INVALID');
});

test('resolver accepts bearer tokens, returns null otherwise', async () => {
  const r = new SignedTokenActorResolver({ secret: INSECURE_DEV_SECRET, now: () => NOW });
  expect(await r.resolve({})).toBeNull();
  expect(await r.resolve({ authorization: 'Basic abc' })).toBeNull();
  expect(await r.resolve({ authorization: 'Bearer bogus' })).toBeNull();
  const token = mintActorToken(INSECURE_DEV_SECRET, actor, NOW, 3600);
  expect(await r.resolve({ authorization: `Bearer ${token}` })).toEqual(actor);
  expect(() => new SignedTokenActorResolver({ secret: '' })).toThrow('ACTOR_SECRET_MISSING');
});

test('tenant claims round-trip; legacy tokens verify without scope', () => {
  const scoped = mintActorToken(INSECURE_DEV_SECRET, { ...actor, market: 'in', orgId: 'org_1' }, NOW, 3600);
  expect(verifyActorToken(INSECURE_DEV_SECRET, scoped, NOW)).toEqual({ ...actor, market: 'in', orgId: 'org_1' });
  // Legacy payload shape (no m/o keys) still verifies.
  const legacy = mintActorToken(INSECURE_DEV_SECRET, actor, NOW, 3600);
  expect(verifyActorToken(INSECURE_DEV_SECRET, legacy, NOW)).toEqual(actor);
  expect(() => mintActorToken(INSECURE_DEV_SECRET, { ...actor, market: 'in.x' }, NOW, 60)).toThrow('ACTOR_FIELD_INVALID');
});

test('tenant enforcement defaults legacy scope to the default market', () => {
  expect(() => assertSameTenant({ ...actor }, {})).not.toThrow();
  expect(() => assertSameTenant({ ...actor, market: 'in' }, { market: 'ae' })).toThrow('TENANT_MISMATCH:in!=ae');
  expect(() => assertSameTenant({ ...actor, orgId: 'a' }, { orgId: 'b' })).toThrow('TENANT_ORG_MISMATCH');
  expect(() => assertSameTenant({ ...actor, orgId: 'a' }, { orgId: 'a' })).not.toThrow();
  expect(() => assertSameTenant({ ...actor, orgId: 'a' }, {})).not.toThrow();
});

test('actor token secret refuses to fall back in production', () => {
  // Dev/test keep the shared fallback, so existing fixtures that mint with
  // INSECURE_DEV_SECRET still verify against it.
  expect(resolveActorTokenSecret({})).toBe(INSECURE_DEV_SECRET);
  expect(resolveActorTokenSecret({ NODE_ENV: 'development' })).toBe(INSECURE_DEV_SECRET);
  expect(resolveActorTokenSecret({ NODE_ENV: 'test' })).toBe(INSECURE_DEV_SECRET);

  // An explicit key wins everywhere — and an empty string counts as unset.
  expect(resolveActorTokenSecret({ SESSION_SIGNING_KEY: 'k' })).toBe('k');
  expect(resolveActorTokenSecret({ NODE_ENV: 'production', SESSION_SIGNING_KEY: 'k' })).toBe('k');
  expect(() => resolveActorTokenSecret({ NODE_ENV: 'production', SESSION_SIGNING_KEY: '' })).toThrow(
    SESSION_SIGNING_KEY_MISSING,
  );

  // THE GUARD: production without a key must refuse rather than sign with a
  // constant committed to this repository, which would let anyone mint a
  // valid token for any userId/sessionId/roles tuple.
  expect(() => resolveActorTokenSecret({ NODE_ENV: 'production' })).toThrow(SESSION_SIGNING_KEY_MISSING);
  expect(() => resolveActorTokenSecret({ NODE_ENV: 'production' })).toThrow(/SESSION_SIGNING_KEY is not set/);
});

test('the production guard is deferred to resolve(), not module load', async () => {
  // Several BFF modules build their resolver at module scope, and Next.js
  // imports those modules during `next build` (NODE_ENV=production, no
  // runtime secrets) — so construction must not touch the environment.
  const denied = new SignedTokenActorResolver({
    secret: () => resolveActorTokenSecret({ NODE_ENV: 'production' }),
    now: () => NOW,
  });
  // No bearer header: nothing to verify, nothing to guard.
  expect(await denied.resolve({})).toBeNull();
  // Presenting a token surfaces the misconfiguration loudly instead of a
  // silent 401 an operator would read as "bad credentials".
  await expect(denied.resolve({ authorization: 'Bearer x.y' })).rejects.toThrow(SESSION_SIGNING_KEY_MISSING);

  const dev = new SignedTokenActorResolver({ secret: () => resolveActorTokenSecret({ NODE_ENV: 'test' }) });
  expect(await dev.resolve({ authorization: 'Bearer x.y' })).toBeNull();

  // The lazy form still round-trips a real token under an explicit key.
  const token = mintActorToken('rotated-key', actor, NOW, 3600);
  const wired = new SignedTokenActorResolver({
    secret: () => resolveActorTokenSecret({ SESSION_SIGNING_KEY: 'rotated-key' }),
    now: () => NOW,
  });
  expect(await wired.resolve({ authorization: `Bearer ${token}` })).toEqual(actor);
});
