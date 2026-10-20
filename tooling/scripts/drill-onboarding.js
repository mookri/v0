// Live staging drill: full applicant flow against a booted partner app.
// Prerequisites: partner running with DRILL_OTP_CODE set; tokens minted via
// tooling/scripts/mint-actor-token.ts into %TEMP%/tok-a.txt + tok-r.txt.
// Usage: node tooling/scripts/drill-onboarding.js
const BASE = 'http://localhost:3002';
const fs = require('fs');
const path = require('path');

const NUL = String.fromCharCode(0);

function readToken(name) {
  const tmp = process.env.TEMP || process.env.TMP || '/tmp';
  const file = path.join(tmp, name);
  const buf = fs.readFileSync(file);
  const text = buf.includes(0) ? buf.toString('utf16le') : buf.toString('utf8');
  return text.replace(/^﻿/, '').trim().split('\n').pop().trim();
}

const applicant = readToken('tok-a.txt');
const reviewer = readToken('tok-r.txt');

async function call(method, p, token, body) {
  const res = await fetch(BASE + p, {
    method,
    headers: { 'Content-Type': 'application/json', authorization: `Bearer ${token}` },
    body: body ? JSON.stringify(body) : undefined,
  });
  const json = await res.json();
  console.log(`${method} ${p} -> ${res.status}`, JSON.stringify(json.data || json.error));
  return { status: res.status, json };
}

(async () => {
  const started = await call('POST', '/api/onboarding/start', applicant, { track: 'pandit' });
  const id = started.json.data.applicationId;
  const steps = {
    identity: { name: 'Drill Pandit', phone: '+910000000001' },
    credentials: { certifications: ['vedic'] },
    services: ['lakshmi-puja'],
    areas: ['delhi'],
    availability: { set: true },
    documents: ['doc_drill'],
    agreement: { accepted: true },
  };
  for (const [step, value] of Object.entries(steps)) {
    await call('PUT', `/api/onboarding/${id}/step/${step}`, applicant, { value });
  }
  const begun = await call('POST', `/api/onboarding/${id}/submit`, applicant, {});
  const challengeId = begun.json.data.challengeId;
  await call('POST', `/api/onboarding/${id}/submit/confirm`, applicant, { challengeId, code: '000000' });
  await call('POST', `/api/onboarding/${id}/submit/confirm`, applicant, { challengeId, code: '123456' });
  await call('GET', '/api/review/queue', reviewer);
  await call('POST', `/api/review/${id}/decision`, reviewer, { decision: 'APPROVED' });
  await call('GET', `/api/onboarding/${id}/status`, applicant);
})().catch((e) => {
  console.error('DRILL_FAILED', e);
  process.exit(1);
});
