import { test, expect } from 'vitest';
import { POST as login } from '@apps/partner/app/api/auth/login/route';
import { POST as logout } from '@apps/partner/app/api/auth/logout/route';
import { GET as getApp } from '@apps/partner/app/api/onboarding/[id]/route';
import { POST as start } from '@apps/partner/app/api/onboarding/start/route';
import { partnerIdentities, partnerVerifier, SESSION_COOKIE } from '@apps/partner/server/auth';
import { mintActorToken, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';

const BASE = 'https://partner.navdhya.com';
const NOW_ISO = () => new Date().toISOString();

async function seedUser(userId = 'cook1', proof = 'pw-cookie') {
  const nowIso = NOW_ISO();
  await partnerIdentities.save({
    id: userId,
    status: 'ACTIVE',
    emailVerified: false,
    phoneVerified: false,
    createdAt: nowIso,
    updatedAt: nowIso,
  });
  partnerVerifier.seed(userId, proof);
}

const post = (p: string, headers: Record<string, string> = {}, body?: unknown) =>
  new Request(BASE + p, { method: 'POST', headers: { 'Content-Type': 'application/json', ...headers }, body: body ? JSON.stringify(body) : undefined });

test('login issues httpOnly session cookie; bad credentials rejected', async () => {
  await seedUser();
  const bad = await login(post('/api/auth/login', {}, { identityId: 'cook1', proof: 'wrong' }));
  expect(bad.status).toBe(401);
  const res = await login(post('/api/auth/login', {}, { identityId: 'cook1', proof: 'pw-cookie' }));
  expect(res.status).toBe(200);
  const setCookie = res.headers.get('Set-Cookie') ?? '';
  expect(setCookie).toContain(`${SESSION_COOKIE}=`);
  expect(setCookie).toContain('HttpOnly');
  expect(setCookie).toContain('SameSite=Lax');
  expect(setCookie).not.toContain('pw-cookie');
});

test('cookie authenticates onboarding reads; logout clears and revokes', async () => {
  await seedUser('cook2');
  const logged = await login(post('/api/auth/login', {}, { identityId: 'cook2', proof: 'pw-cookie' }));
  const token = (logged.headers.get('Set-Cookie') ?? '').split(';')[0]!.split('=')[1]!;
  expect(token.length).toBeGreaterThan(20);

  const started = await start(
    new Request(`${BASE}/api/onboarding/start`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', authorization: `Bearer ${token}` },
      body: JSON.stringify({ track: 'pandit' }),
    }),
  );
  const { data: { applicationId } } = (await started.json()) as { data: { applicationId: string } };

  const viaCookie = await getApp(
    new Request(`${BASE}/api/onboarding/${applicationId}`, { headers: { cookie: `${SESSION_COOKIE}=${token}` } }),
    { params: Promise.resolve({ id: applicationId }) },
  );
  expect(viaCookie.status).toBe(200);

  const out = await logout(
    new Request(`${BASE}/api/auth/logout`, { method: 'POST', headers: { cookie: `${SESSION_COOKIE}=${token}` } }),
  );
  expect(out.status).toBe(200);
  expect(out.headers.get('Set-Cookie') ?? '').toContain('Max-Age=0');

  const dead = await getApp(
    new Request(`${BASE}/api/onboarding/${applicationId}`, { headers: { cookie: `${SESSION_COOKIE}=${token}` } }),
    { params: Promise.resolve({ id: applicationId }) },
  );
  expect(dead.status).toBe(401);
});

test('cookie helpers reject malformed values', async () => {
  const res = await getApp(new Request(`${BASE}/api/onboarding/x`, { headers: { cookie: `${SESSION_COOKIE}=bogus` } }), {
    params: Promise.resolve({ id: 'x' }),
  });
  expect(res.status).toBe(401);
  expect(mintActorToken).toBeDefined();
  expect(INSECURE_DEV_SECRET.length).toBeGreaterThan(0);
});
