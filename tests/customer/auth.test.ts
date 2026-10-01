import { test, expect } from 'vitest';
import { POST as login } from '@apps/customer/app/api/auth/login/route';
import { POST as logout } from '@apps/customer/app/api/auth/logout/route';
import { GET as events } from '@apps/customer/app/api/events/route';

const loginAs = (body: unknown) =>
  login(new Request('https://app.navdhya.com/api/auth/login', { method: 'POST', body: JSON.stringify(body) }));

test('login validates input and rejects bad credentials', async () => {
  const badBody = await login(new Request('https://app.navdhya.com/api/auth/login', { method: 'POST', body: '!!' }));
  expect(badBody.status).toBe(422);
  const missing = await loginAs({});
  expect(missing.status).toBe(422);
  const unknown = await loginAs({ identityId: 'ghost', proof: 'x' });
  expect(unknown.status).toBe(401);
});

test('seeded dev identity logs in; logout revokes the session', async () => {
  process.env['SEED_DEV_IDENTITY'] = '1';
  process.env['SEED_DEV_PROOF'] = 's3cret';
  try {
    const res = await loginAs({ identityId: 'dev-user', proof: 's3cret' });
    expect(res.status).toBe(200);
    const body = (await res.json()) as { data: { token: string; sessionId: string } };
    expect(typeof body.data.token).toBe('string');
    const wrong = await loginAs({ identityId: 'dev-user', proof: 'nope' });
    expect(wrong.status).toBe(401);

    // Live stream works before logout…
    const live = await events(
      new Request('https://app.navdhya.com/api/events', { headers: { authorization: `Bearer ${body.data.token}` } }),
    );
    expect(live.status).toBe(200);
    await live.body!.cancel();

    // …and dies after logout (session-bound handshake).
    const out = await logout(
      new Request('https://app.navdhya.com/api/auth/logout', {
        method: 'POST',
        headers: { authorization: `Bearer ${body.data.token}` },
      }),
    );
    expect(out.status).toBe(200);
    const dead = await events(
      new Request('https://app.navdhya.com/api/events', { headers: { authorization: `Bearer ${body.data.token}` } }),
    );
    expect(dead.status).toBe(401);
  } finally {
    delete process.env['SEED_DEV_IDENTITY'];
    delete process.env['SEED_DEV_PROOF'];
  }
});

test('logout without a token is unauthenticated', async () => {
  const res = await logout(new Request('https://app.navdhya.com/api/auth/logout', { method: 'POST' }));
  expect(res.status).toBe(401);
});
