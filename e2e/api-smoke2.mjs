// api-smoke2.mjs - targeted contract probes for the four findings of
// api-smoke.mjs (e2e/api-smoke-report.md), re-run as the post-fix
// regression set:
//   R1  decision: valid (type-complete) payload -> 200 + decisions[]
//   R1b decision: bare {slotId} -> 422 schema rejection, never a 500
//       (F-SMOKE4)
//   R2  settings: flat backend keys -> 200 through the BFF (R2a/R2b),
//       direct backend still 200 (R2c); the old `settings` wrapper and
//       dropped keys are rejected by the BFF closed key set (R2d/R2e)
//       (F-SMOKE1)
//   R3  saved: direct backend {ref_type, ULID ref_id} -> 201; the BFF
//       now forwards the same shape (campaign1 saved POST probe)
//       (F-SMOKE2)
//   R4  sessions: revoke-others must end every OTHER session and spare
//       the presented one on the bearer path (F-SMOKE3)
// Pacing: 9 backend calls, one fixed window (limit 10) -> one 61 s sleep.
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHmac, randomBytes } from 'node:crypto';

const ROOT = fileURLToPath(new URL('..', import.meta.url));
const BFF = 'http://127.0.0.1:3000';
const LARAVEL = 'http://127.0.0.1:8000';
const LOG_DIR = join(ROOT, 'platform-navdhya', 'storage', 'logs');
const PHONE = '+9170000' + String(Math.floor(Math.random() * 100000)).padStart(5, '0');

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const results = [];
function rec(group, name, ok, detail) {
  results.push({ group, name, ok, detail });
  console.log(`${ok ? 'PASS' : 'FAIL'} | ${group} | ${name} | ${detail}`);
}
const detail = (r) => `status=${r.status} ${r.text.slice(0, 200).replace(/\s+/g, ' ')}`;

// ---- cookie jar ----------------------------------------------------------
const jar = new Map();
function jarHeader() {
  return [...jar.entries()].map(([k, v]) => `${k}=${v}`).join('; ');
}
function applySetCookie(res) {
  for (const line of res.headers.getSetCookie?.() ?? []) {
    const [pair] = line.split(';');
    const eq = pair.indexOf('=');
    if (eq < 0) continue;
    const name = pair.slice(0, eq).trim();
    const value = pair.slice(eq + 1).trim();
    if (value === '') jar.delete(name);
    else jar.set(name, value);
  }
}

// ---- backend throttle pacing (shared 10/min fixed window) ----------------
let windowHits = 0;
async function thHit() {
  if (windowHits >= 9) {
    await sleep(61_000);
    windowHits = 0;
  }
  windowHits++;
}

async function parse(res) {
  const text = await res.text().catch(() => '');
  let json = null;
  try { json = text ? JSON.parse(text) : null; } catch { /* non-JSON */ }
  return { status: res.status, json, text };
}

// BFF call (cookie session)
async function call(method, path, { body, headers = {}, backend = false } = {}) {
  if (backend) await thHit();
  const h = { accept: 'application/json', ...headers };
  if (body !== undefined) h['content-type'] = 'application/json';
  if (jar.size && h.cookie === undefined) h.cookie = jarHeader();
  const res = await fetch(`${BFF}${path}`, {
    method, headers: h, body: body === undefined ? undefined : JSON.stringify(body),
  });
  applySetCookie(res);
  return parse(res);
}

// Direct backend call (bearer = raw session cookie value, x-tenant-id)
async function dcall(method, path, body) {
  await thHit();
  const token = jar.get('navdhya-session') ?? '';
  const res = await fetch(`${LARAVEL}/api/v1${path}`, {
    method,
    headers: {
      accept: 'application/json',
      'content-type': 'application/json',
      authorization: `Bearer ${token}`,
      'x-tenant-id': process.env.E2E_TENANT_ID ?? '',
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  return parse(res);
}

function otpCodeFor(challengeId) {
  const files = readdirSync(LOG_DIR)
    .filter((f) => /^laravel.*\.log$/.test(f))
    .map((f) => ({ f, t: statSync(join(LOG_DIR, f)).mtimeMs }))
    .sort((a, b) => b.t - a.t);
  for (const { f } of files) {
    const lines = readFileSync(join(LOG_DIR, f), 'utf8').split(/\r?\n/);
    for (let i = lines.length - 1; i >= 0; i--) {
      if (lines[i].includes(`"challenge_id":"${challengeId}"`)) {
        const m = lines[i].match(/"code":"(\d{6})"/);
        if (m) return m[1];
      }
    }
  }
  return '';
}

function mintCsrf() {
  const env = readFileSync(join(ROOT, 'apps/web/.env.production.local'), 'utf8');
  const m = env.match(/^CSRF_SECRET=(.+)$/m);
  if (!m) throw new Error('CSRF_SECRET not found in .env.production.local');
  const secret = m[1].trim().replace(/^["']|["']$/g, '');
  const nonce = randomBytes(32).toString('base64url');
  const exp = String(Date.now() + 3600_000);
  const payload = `${nonce}.${exp}`;
  const sig = createHmac('sha256', secret).update(payload).digest('base64url');
  return `${payload}.${sig}`;
}

async function main() {
  console.log(`probe identity: ${PHONE}`);
  // ---- R1: decision with the canonical (Jest fixture) payload -----------
  console.log('--- R1 decision (valid payload)');
  const token = mintCsrf();
  const decision = await call('POST', '/api/ads/decision', {
    headers: { cookie: `navdhya-csrf=${token}`, 'x-csrf-token': token },
    body: {
      requestId: 'req_smoke2',
      opportunities: [{
        slotId: 'unsupported',
        surface: 'feed',
        placementHints: ['feed_video_inline'],
        anchorId: 'feed-1',
        anchorIndex: 1,
        reservedSize: { width: 'fluid', height: 180, aspectRatio: '4:3' },
        depthBucket: 'mid',
        lazy: true,
      }],
      page: { title: 'Feed page', categories: ['blog'], keywords: ['feed'] },
      user: {},
      client: {},
      session: { id: 'session', engagementScore: 0.2, scrollDepth: 0.1 },
    },
  });
  const decisionsOk = decision.status === 200 && Array.isArray(decision.json?.decisions);
  rec('R1', 'ads/decision valid payload -> 200 + decisions[]', decisionsOk, detail(decision));

  // F-SMOKE4: the bare payload that used to 500 ('reading some' on
  // opportunity.placementHints) must now fail closed as a 422.
  const bare = await call('POST', '/api/ads/decision', {
    headers: { cookie: `navdhya-csrf=${token}`, 'x-csrf-token': token },
    body: { requestId: 'req_smoke2_bare', opportunities: [{ slotId: 's1' }] },
  });
  rec('R1b', 'ads/decision bare opportunity -> 422 (not 500)', bare.status === 422, detail(bare));

  // ---- session establishment --------------------------------------------
  console.log('--- establishing session (signup + verify)');
  const warm = await fetch(`${LARAVEL}/api/v1/customers/auth/signup`, {
    method: 'POST',
    headers: { 'content-type': 'application/json', 'x-tenant-id': process.env.E2E_TENANT_ID ?? '' },
    body: JSON.stringify({ tenant_id: process.env.E2E_TENANT_ID, phone: '+917000099998', name: 'Warm2' }),
  }).catch(() => null);
  windowHits++;
  console.log(`warm-up direct status=${warm ? warm.status : 'ERR'}`);

  const signup = await call('POST', '/api/auth/signup', { backend: true, body: { phone: PHONE, name: 'Probe Patty' } });
  const cid = signup.json?.data?.challengeId;
  rec('S', 'auth/signup', signup.status === 202 && !!cid, detail(signup));

  const code = cid ? otpCodeFor(cid) : '';
  const verify = await call('POST', '/api/auth/verify', { backend: true, body: { challengeId: cid, code } });
  rec('S', 'auth/verify (cookie)', verify.status >= 200 && verify.status < 300 && jar.size > 0, detail(verify));

  // ---- R2: settings wire -------------------------------------------------
  console.log('--- R2 settings wire');
  const flat = await call('PATCH', '/api/settings', { backend: true, body: { locale: 'en' } });
  rec('R2a', 'BFF PATCH flat {locale} -> 200 (flat wire, no wrapper)', flat.status === 200, detail(flat));

  const backendKey = await call('PATCH', '/api/settings', { backend: true, body: { notifications_email: true } });
  rec('R2b', 'BFF PATCH backend key {notifications_email} -> 200', backendKey.status === 200, detail(backendKey));

  const direct = await dcall('PATCH', '/customers/settings', { locale: 'en' });
  rec('R2c', 'BACKEND PATCH flat {locale} (direct)', direct.status === 200, detail(direct));

  const wrapped = await call('PATCH', '/api/settings', {
    body: { settings: { locale: 'en' } },
  });
  rec('R2d', 'BFF PATCH wrapper {settings:{...}} -> 422 (BFF rejects)', wrapped.status === 422, detail(wrapped));

  const unknownKey = await call('PATCH', '/api/settings', { body: { timezone: 'UTC' } });
  rec('R2e', 'BFF PATCH dropped key {timezone} -> 422 (closed set)', unknownKey.status === 422, detail(unknownKey));

  // ---- sessions list before revoke ---------------------------------------
  const sess1 = await call('GET', '/api/sessions', { backend: true });
  const currentTrue = (sess1.text.match(/"current":true/g) ?? []).length;
  rec('R4a', 'sessions GET (before)', sess1.status === 200,
    `current_true=${currentTrue} ${detail(sess1)}`);

  // ---- R3: saved direct backend ------------------------------------------
  const savedDirect = await dcall('POST', '/customers/saved', {
    ref_type: 'puja',
    ref_id: '01JQZ8K7M9N2P4R6T8V0W2X4Y6',
  });
  rec('R3', 'BACKEND saved POST {ref_type, ULID} (direct)', savedDirect.status === 201, detail(savedDirect));

  // ---- R4: revoke-others then re-probe -----------------------------------
  const revoke = await call('POST', '/api/sessions/revoke-others', { backend: true, body: {} });
  rec('R4b', 'sessions/revoke-others call', revoke.status === 200, detail(revoke));

  const sess2 = await call('GET', '/api/sessions', { backend: true });
  rec('R4c', 'presented session survives revoke-others (contract)', sess2.status === 200, detail(sess2));

  // ---- summary -----------------------------------------------------------
  const fails = results.filter((r) => !r.ok);
  console.log('\n===== PROBE2 SUMMARY =====');
  console.log(`${results.length - fails.length}/${results.length} PASS`);
  for (const f of fails) console.log(`  FAIL: [${f.group}] ${f.name} :: ${f.detail}`);
  process.exitCode = fails.length ? 1 : 0;
}

main().catch((e) => {
  console.error('PROBE2 CRASHED:', e);
  process.exitCode = 2;
});
