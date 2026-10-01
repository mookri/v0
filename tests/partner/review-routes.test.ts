import { test, expect } from 'vitest';
import { GET as queue } from '@apps/partner/app/api/review/queue/route';
import { POST as decide } from '@apps/partner/app/api/review/[id]/decision/route';
import { onboardingApps } from '@apps/partner/server/onboarding';
import { getReviewQueue } from '@apps/partner/server/review';
import { reviewAudit } from '@apps/partner/server/review';
import { __issuePartnerTestToken } from '@apps/partner/server/auth';

const BASE = 'https://partner.navdhya.com';
const bearer = async (userId: string, roles: string[]) => `Bearer ${await __issuePartnerTestToken(userId, roles)}`;
const bearerScoped = async (userId: string, roles: string[], market: string) =>
  `Bearer ${await __issuePartnerTestToken(userId, roles, market)}`;
const req = async (method: string, url: string, userId: string | null, roles: string[] = ['partner'], body?: unknown) =>
  new Request(url, {
    method,
    headers: { ...(userId ? { authorization: await bearer(userId, roles) } : {}), 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : undefined,
  });
const reqScoped = async (method: string, url: string, userId: string, roles: string[], market: string, body?: unknown) =>
  new Request(url, {
    method,
    headers: { authorization: await bearerScoped(userId, roles, market), 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : undefined,
  });

async function seed(status: 'SUBMITTED' | 'UNDER_REVIEW' = 'UNDER_REVIEW', id = 'ra_1', market?: string) {
  await onboardingApps.save({
    id,
    identityId: 'p1',
    type: 'PANDIT',
    track: 'pandit',
    trackVersion: 'pandit-v1',
    ...(market ? { market } : {}),
    status,
    submittedAt: new Date(Date.now() - 3600_000).toISOString(),
  });
  return id;
}

test('review routes gate roles; queue carries SLA state', async () => {
  const anon = await queue(await req('GET', `${BASE}/api/review/queue`, null));
  expect(anon.status).toBe(401);
  const applicant = await queue(await req('GET', `${BASE}/api/review/queue`, 'p1'));
  expect(applicant.status).toBe(403);
  await seed();
  const res = await queue(await req('GET', `${BASE}/api/review/queue`, 'r1', ['reviewer']));
  expect(res.status).toBe(200);
  const body = (await res.json()) as { data: { items: { id: string; slaBreached: boolean }[] } };
  const item = body.data.items.find((i) => i.id === 'ra_1');
  expect(item?.slaBreached).toBe(false);
});

test('review queue and decisions enforce market scope', async () => {
  await seed('UNDER_REVIEW', 'ra_in');
  await seed('UNDER_REVIEW', 'ra_ae', 'ae');
  const res = await queue(await reqScoped('GET', `${BASE}/api/review/queue`, 'r1', ['reviewer'], 'in'));
  const body = (await res.json()) as { data: { items: { id: string }[] } };
  expect(body.data.items.map((i) => i.id)).toContain('ra_in');
  expect(body.data.items.map((i) => i.id)).not.toContain('ra_ae');
  const foreign = await decide(
    await reqScoped('POST', `${BASE}/api/review/ra_ae/decision`, 'r1', ['reviewer'], 'in', { decision: 'APPROVED' }),
    { params: Promise.resolve({ id: 'ra_ae' }) },
  );
  expect(foreign.status).toBe(403);
  const home = await decide(
    await reqScoped('POST', `${BASE}/api/review/ra_in/decision`, 'r1', ['reviewer'], 'in', { decision: 'APPROVED' }),
    { params: Promise.resolve({ id: 'ra_in' }) },
  );
  expect(home.status).toBe(200);
});

test('shared queue helper scopes and flags consistently with the route', async () => {
  await seed('SUBMITTED', 'rq_1');
  await seed('UNDER_REVIEW', 'rq_2', 'ae');
  const items = await getReviewQueue('in', new Date().toISOString());
  expect(items.map((i) => i.id)).toContain('rq_1');
  expect(items.map((i) => i.id)).not.toContain('rq_2');
  expect(items.find((i) => i.id === 'rq_1')?.slaBreached).toBe(false);
});

test('first reviewer touch auto-triages SUBMITTED into review', async () => {
  const id = await seed('SUBMITTED', 'ra_triage');
  const res = await decide(await reqScoped('POST', `${BASE}/api/review/${id}/decision`, 'r1', ['reviewer'], 'in', { decision: 'APPROVED' }), {
    params: Promise.resolve({ id }),
  });
  expect(res.status).toBe(200);
});

test('approve flows; rejections need countersign + reason; audit appended', async () => {
  const id = await seed('UNDER_REVIEW', 'ra_2');
  const approve = await decide(await req('POST', `${BASE}/api/review/${id}/decision`, 'r1', ['reviewer'], { decision: 'APPROVED' }), {
    params: Promise.resolve({ id }),
  });
  expect(approve.status).toBe(200);
  expect((await reviewAudit.listByApplication(id)).map((r) => r.decision)).toEqual(['APPROVED']);
  const approvedBody = (await approve.json()) as { data: { activated: { partnerId: string; eligible: boolean } } };
  expect(approvedBody.data.activated).toEqual({ partnerId: 'p_p1', eligible: true });

  const id2 = await seed('UNDER_REVIEW', 'ra_3');
  const naked = await decide(await req('POST', `${BASE}/api/review/${id2}/decision`, 'r1', ['reviewer'], { decision: 'REJECTED' }), {
    params: Promise.resolve({ id: id2 }),
  });
  expect(naked.status).toBe(400);
  const selfSigned = await decide(
    await req('POST', `${BASE}/api/review/${id2}/decision`, 'r1', ['reviewer'], { decision: 'REJECTED', secondReviewer: 'r1' }),
    { params: Promise.resolve({ id: id2 }) },
  );
  expect(selfSigned.status).toBe(400);
  const rejected = await decide(
    await req('POST', `${BASE}/api/review/${id2}/decision`, 'r1', ['reviewer'], {
      decision: 'REJECTED',
      reasonCode: 'OTHER',
      secondReviewer: 'r2',
    }),
    { params: Promise.resolve({ id: id2 }) },
  );
  expect(rejected.status).toBe(200);
  const records = await reviewAudit.listByApplication(id2);
  expect(records[0]?.reviewerId).toBe('r1');
  expect(records[0]?.note).toContain('countersigned:r2');
});
