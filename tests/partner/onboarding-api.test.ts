import { test, expect, beforeEach } from 'vitest';
import { POST as start } from '@apps/partner/app/api/onboarding/start/route';
import { GET as getApp } from '@apps/partner/app/api/onboarding/[id]/route';
import { PUT as putStep } from '@apps/partner/app/api/onboarding/[id]/step/[step]/route';
import { POST as submit } from '@apps/partner/app/api/onboarding/[id]/submit/route';
import { POST as confirm } from '@apps/partner/app/api/onboarding/[id]/submit/confirm/route';
import { POST as documents } from '@apps/partner/app/api/onboarding/[id]/documents/route';
import { GET as status } from '@apps/partner/app/api/onboarding/[id]/status/route';
import { __resetEvidenceForTests } from '@apps/partner/server/onboarding';
import { __issuePartnerTestToken } from '@apps/partner/server/auth';

beforeEach(() => {
  __resetEvidenceForTests();
});

const bearer = async (userId: string) => `Bearer ${await __issuePartnerTestToken(userId)}`;
const auth = async (userId: string) => ({ authorization: await bearer(userId) });
const json = async (method: string, url: string, userId: string | null, body?: unknown) =>
  new Request(url, {
    method,
    headers: { ...(userId ? await auth(userId) : {}), 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : undefined,
  });
const BASE = 'https://partner.navdhya.com';

const EVIDENCE: Record<string, unknown> = {
  identity: { name: 'Ravi', phone: '+91' },
  credentials: { certifications: ['vedic'] },
  services: ['lakshmi-puja'],
  areas: ['delhi'],
  availability: { set: true },
  documents: ['doc_1'],
  agreement: { accepted: true },
};

async function startApp(userId = 'p1', track = 'pandit'): Promise<string> {
  const res = await start(await json('POST', `${BASE}/api/onboarding/start`, userId, { track }));
  expect(res.status).toBe(201);
  const body = (await res.json()) as { data: { applicationId: string; trackVersion: string } };
  expect(body.data.trackVersion).toBe('pandit-v1');
  return body.data.applicationId;
}

async function fillSteps(id: string, userId: string, evidence: Record<string, unknown> = EVIDENCE) {
  for (const [step, value] of Object.entries(evidence)) {
    const res = await putStep(await json('PUT', `${BASE}/api/onboarding/${id}/step/${step}`, userId, { value }), {
      params: Promise.resolve({ id, step }),
    });
    expect(res.status).toBe(200);
  }
}

test('start validates track; draft reports needs; unknown step rejected', async () => {
  const bad = await start(await json('POST', `${BASE}/api/onboarding/start`, 'p1', { track: 'nope' }));
  expect(bad.status).toBe(400);
  const anon = await start(await json('POST', `${BASE}/api/onboarding/start`, null, { track: 'pandit' }));
  expect(anon.status).toBe(401);
  const id = await startApp();
  const res = await getApp(await json('GET', `${BASE}/api/onboarding/${id}`, 'p1'), { params: Promise.resolve({ id }) });
  const body = (await res.json()) as { data: { needs: string[]; steps: { id: string }[] } };
  expect(body.data.needs).toContain('identity');
  expect(body.data.steps.length).toBe(8);
  const unknown = await putStep(await json('PUT', `${BASE}/api/onboarding/${id}/step/nope`, 'p1', { value: 1 }), {
    params: Promise.resolve({ id, step: 'nope' }),
  });
  expect(unknown.status).toBe(400);
  const foreign = await getApp(await json('GET', `${BASE}/api/onboarding/${id}`, 'p2'), { params: Promise.resolve({ id }) });
  expect(foreign.status).toBe(403);
});

test('submit/confirm are backend-owned: mock mode refuses both phases with 503', async () => {
  const id = await startApp();
  await fillSteps(id, 'p1');
  // MSG91: the challenge lifecycle moved to Laravel; the BFF routes proxy it
  // and fail closed when the backend is not configured.
  const begun = await submit(await json('POST', `${BASE}/api/onboarding/${id}/submit`, 'p1', {}), {
    params: Promise.resolve({ id }),
  });
  expect(begun.status).toBe(503);
  const confirmed = await confirm(
    await json('POST', `${BASE}/api/onboarding/${id}/submit/confirm`, 'p1', { challenge_id: 'ch_1', token: 'tok' }),
    { params: Promise.resolve({ id }) },
  );
  expect(confirmed.status).toBe(503);
});

test('applicant routes enforce market scope', async () => {
  const id = await startApp('p1');
  const aeBearer = `Bearer ${await __issuePartnerTestToken('p1', ['partner'], 'ae')}`;
  const res = await getApp(
    new Request(`${BASE}/api/onboarding/${id}`, { headers: { authorization: aeBearer } }),
    { params: Promise.resolve({ id }) },
  );
  expect(res.status).toBe(403);
  const home = await getApp(await json('GET', `${BASE}/api/onboarding/${id}`, 'p1'), { params: Promise.resolve({ id }) });
  expect(home.status).toBe(200);
});

test('documents issuance scopes keys and validates input', async () => {
  const id = await startApp();
  const res = await documents(
    await json('POST', `${BASE}/api/onboarding/${id}/documents`, 'p1', { kind: 'id-proof', contentLength: 100, sha256: 'abc' }),
    { params: Promise.resolve({ id }) },
  );
  expect(res.status).toBe(201);
  const body = (await res.json()) as { data: { documentId: string; url: string } };
  expect(body.data.url).toContain('onboarding/');
  const bad = await documents(await json('POST', `${BASE}/api/onboarding/${id}/documents`, 'p1', { kind: 'video' }), {
    params: Promise.resolve({ id }),
  });
  expect(bad.status).toBe(400);
  const st = (await (
    await status(await json('GET', `${BASE}/api/onboarding/${id}/status`, 'p1'), { params: Promise.resolve({ id }) })
  ).json()) as { data: { documents: { kind: string; status: string }[] } };
  expect(st.data.documents).toEqual([{ id: body.data.documentId, kind: 'id-proof', status: 'PENDING', reasonCode: null }]);
});
