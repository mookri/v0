import { test, expect } from 'vitest';
import {
  mintActorToken,
  verifyActorToken,
  SignedTokenActorResolver,
  INSECURE_DEV_SECRET,
} from '@navdhya/identity/src/application/token-actor';

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
