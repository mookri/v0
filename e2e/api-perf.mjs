// api-perf.mjs — latency baseline harness (perf mission Step 01).
//
// Measures client-observed p50/p95 per endpoint through the real stack
// (BFF :3000 → Laravel :8000 → MySQL) and joins every sample to the
// server-side lines by request id:
//
//   client  — performance.now() around the BFF fetch (full round trip)
//   [upstream] — BFF → Laravel hop, logged by adapters/backend/
//             laravel-client.ts → e2e/.logs/smoke-bff-out.log
//   [perf]  — Laravel total + sql_ms + query count, logged by
//             Platform/Http/Middleware/RequestTiming.php →
//             platform-navdhya/storage/logs/laravel*.log
//
// The rid travels envelope → Navdhya-Request-Id header → backend
// AttachRequestId, so one id joins all three layers.
//
// Throttles are PACED, not fought:
//   * backend `throttle:10,1` = one shared fixed window per IP for all
//     BFF→Laravel traffic (127.0.0.1): batches of 8 backend hits, then
//     `php artisan cache:clear` resets the window (the same reset
//     smoke.ps1 performs at boot; cache store = database). One warm-up
//     backend hit follows each reset so no sample measures a cold
//     cache. Fallback when the clear fails: sleep out the 61s window.
//   * BFF IP-keyed limits key on clientIp → x-forwarded-for first hop,
//     so each call carries a fresh XFF hop: we measure service latency
//     on the allowed path, not throttle enforcement (that is
//     scenarios.mjs' job).
//   * per-identity caps are respected exactly: unique phones for
//     issuance samples; 5 login issuances per known identity (BFF
//     otpIdentity 5/10min AND backend 5/hour both allow exactly 5;
//     redeem preps consume+delete their row so the cap never accrues).
//
// Secrets: only request ids, routes, statuses and durations are logged
// or printed. OTP codes travel from the drill log straight into request
// bodies and are never echoed.
//
// Run: powershell -ExecutionPolicy Bypass -File e2e\smoke.ps1 -Script api-perf.mjs
// Exit 0 iff every endpoint met its sample budget with zero errors.

import { execFileSync } from 'node:child_process';
import { createHmac, randomBytes } from 'node:crypto';
import { existsSync, readFileSync, readdirSync, statSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = fileURLToPath(new URL('..', import.meta.url));
const BFF = 'http://127.0.0.1:3000';
const LOG_DIR = join(ROOT, 'platform-navdhya', 'storage', 'logs');
const BFF_LOG = join(ROOT, 'e2e', '.logs', 'smoke-bff-out.log');
// Timestamped per run — a fixed path gets silently overwritten by the
// next run (the original baseline raw was lost to the first after-run).
const OUT_STAMP = new Date().toISOString().replace(/[:T]/g, '-').slice(0, 19);
const OUT_JSON = join(ROOT, 'e2e', '.logs', `api-perf-${OUT_STAMP}.json`);

if (process.env.PERF_TIMING !== '1') {
  console.error('PERF_TIMING is not set — run through e2e/smoke.ps1 (it sets the env).');
  process.exit(2);
}
if (!process.env.E2E_TENANT_ID && !process.env.CUSTOMER_TENANT_ID) {
  console.error('E2E_TENANT_ID is required (run via e2e/smoke.ps1).');
  process.exit(2);
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const digits = (n) => Array.from({ length: n }, () => Math.floor(Math.random() * 10)).join('');
// +91 7xxxxxxxxx — outside the fixed E2E ranges (98765x / 98770x) and
// outside api-smoke's 70000x prefix, so re-runs never collide.
const newPhone = () => `+917${digits(9)}`;
const CROCKFORD = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
function randomUlid() {
  const bytes = randomBytes(26);
  let out = '';
  for (let i = 0; i < 26; i += 1) out += CROCKFORD[bytes[i] % 32]; // 256 % 32 = 0, unbiased
  return out;
}

// ---- cookie jar (scenarios.mjs pattern) ---------------------------------
class Jar {
  constructor() { this.map = new Map(); }
  apply(res) {
    for (const sc of res.headers.getSetCookie?.() ?? []) {
      const [pair] = sc.split(';');
      const eq = pair.indexOf('=');
      if (eq < 0) continue;
      const name = pair.slice(0, eq).trim();
      const value = pair.slice(eq + 1).trim();
      const expired = /max-age=0/i.test(sc) || (/expires=thu, 01 jan 1970/i.test(sc) && value === '');
      if (expired || value === '') this.map.delete(name);
      else this.map.set(name, value);
    }
  }
  header() {
    return [...this.map.entries()].map(([k, v]) => `${k}=${v}`).join('; ');
  }
  clear() { this.map.clear(); }
}

// ---- backend window batching --------------------------------------------
let backendHits = 0;
const BATCH = 8; // of the backend's 10 — 2 slots of headroom
function resetWindow() {
  try {
    execFileSync('php', ['artisan', 'cache:clear', '--no-ansi'], {
      cwd: join(ROOT, 'platform-navdhya'),
      stdio: 'ignore',
      windowsHide: true,
      env: process.env,
    });
    return true;
  } catch {
    return false;
  }
}
// Warm-up: a real backend hit (unknown login identity → activeTenant +
// findUser queries, no challenge row) that is never recorded as a sample.
async function warmup() {
  backendHits = 1;
  await fetch(`${BFF}/api/auth/otp`, {
    method: 'POST',
    headers: {
      accept: 'application/json',
      'content-type': 'application/json',
      'x-forwarded-for': freshXff(),
    },
    body: JSON.stringify({ identity: newPhone(), purpose: 'login' }),
  }).catch(() => null);
}
async function backendSlot() {
  if (backendHits >= BATCH) {
    if (!resetWindow()) await sleep(61_000);
    backendHits = 0;
    await warmup();
  }
  backendHits += 1;
}

const freshXff = () => `10.${10 + Math.floor(Math.random() * 200)}.${Math.floor(Math.random() * 256)}.${1 + Math.floor(Math.random() * 254)}`;

// ---- timed call ----------------------------------------------------------
async function call(method, path, { jar, body, headers = {}, backend = false } = {}) {
  if (backend) await backendSlot();
  const h = { accept: 'application/json', 'x-forwarded-for': freshXff(), ...headers };
  if (body !== undefined) h['content-type'] = 'application/json';
  if (jar && jar.header()) h.cookie = jar.header();
  const t0 = performance.now();
  const res = await fetch(`${BFF}${path}`, {
    method,
    headers: h,
    redirect: 'manual',
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  let text = '';
  try { text = await res.text(); } catch { /* empty body */ }
  const clientMs = performance.now() - t0; // full round trip incl. body
  if (jar) jar.apply(res);
  let json = null;
  if (text) { try { json = JSON.parse(text); } catch { /* non-JSON */ } }
  return {
    status: res.status,
    json,
    text,
    clientMs,
    rid: json?.requestId ?? json?.error?.requestId ?? '',
  };
}

// ---- drill log OTP codes (api-smoke.mjs pattern) -------------------------
function otpCodeFor(challengeId) {
  if (!challengeId) return '';
  const files = readdirSync(LOG_DIR)
    .filter((f) => /^laravel.*\.log$/.test(f))
    .map((f) => ({ f, t: statSync(join(LOG_DIR, f)).mtimeMs }))
    .sort((a, b) => b.t - a.t);
  for (const { f } of files) {
    const lines = readFileSync(join(LOG_DIR, f), 'utf8').split(/\r?\n/);
    for (let i = lines.length - 1; i >= 0; i -= 1) {
      if (lines[i].includes(`"challenge_id":"${challengeId}"`)) {
        const m = lines[i].match(/"code":"(\d{6})"/);
        if (m) return m[1];
      }
    }
  }
  return '';
}

// ---- TOTP (RFC 6238, SHA1, 30 s, 6 digits — api-smoke.mjs pattern) -------
function base32Decode(s) {
  const A = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  s = s.replace(/=+$/, '').toUpperCase();
  let bits = 0;
  let value = 0;
  const out = [];
  for (const c of s) {
    value = (value << 5) | A.indexOf(c);
    bits += 5;
    if (bits >= 8) { out.push((value >>> (bits - 8)) & 255); bits -= 8; }
  }
  return Buffer.from(out);
}
function totp(secret) {
  const counter = Math.floor(Date.now() / 1000 / 30);
  const buf = Buffer.alloc(8);
  buf.writeBigUInt64BE(BigInt(counter));
  const h = createHmac('sha1', base32Decode(secret)).update(buf).digest();
  const o = h[19] & 15;
  const c = ((h[o] & 127) << 24) | (h[o + 1] << 16) | (h[o + 2] << 8) | h[o + 3];
  return String(c % 1000000).padStart(6, '0');
}
async function totpAfterStep() {
  // Wait for the next 30 s step so MFA replay-state checks never see the
  // same code twice inside one run.
  const rem = 30 - ((Date.now() / 1000) % 30);
  await sleep((rem + 0.7) * 1000);
}

// ---- recording -----------------------------------------------------------
const results = []; // { e, s, ms, rid }
const failures = []; // { e, status, body }
function rec(e, r) {
  results.push({ e, s: r.status, ms: +r.clientMs.toFixed(1), rid: r.rid });
}
function check(name, r, want) {
  const ok = Array.isArray(want) ? want.includes(r.status) : r.status === want;
  if (!ok) {
    failures.push({ e: name, status: r.status, body: (r.text ?? '').replace(/\s+/g, ' ').slice(0, 180) });
  }
  return ok;
}
async function sample(e, n, fn, want) {
  for (let i = 0; i < n; i += 1) {
    const r = await fn(i);
    check(e, r, want);
    rec(e, r);
  }
  console.log(`PERF | ${e} | n=${n} | budget met`);
}
const failedPrep = (e, why) => ({ status: -1, text: `prep failed: ${why}`, clientMs: 0, rid: '' });

// ---- budgets -------------------------------------------------------------
const N_OTP_SIGNUP = 10;    // full issue() DB transaction, fresh identity
const N_OTP_UNKNOWN = 10;   // no-oracle fast path (no issue(), 2 queries)
const N_OTP_KNOWN = 5;      // per identity × 2 identities (cap = exactly 5)
const N_SIGNUP = 10;
const N_VERIFY = 10;        // user creation — bcrypt included
const N_LOGIN = 5;          // per identity × 2 identities
const N_SESSIONS = 15;
const N_SETTINGS = 15;
const N_SAVED = 15;
const N_SAVED_ADD = 10;
const N_MFA_STATUS = 10;
const N_MFA_STEP = 5;       // one per 30 s TOTP window (replay-safe)

// ---- log join ------------------------------------------------------------
function parseUpstream() {
  const map = new Map();
  if (!existsSync(BFF_LOG)) return map;
  const re = /\[upstream\] rid=(\S+) method=(\S+) path=(\S+) status=(\d+) ms=([\d.]+)(?: error=(\S+))?/;
  for (const line of readFileSync(BFF_LOG, 'utf8').split(/\r?\n/)) {
    const i = line.indexOf('[upstream] rid=');
    if (i < 0) continue;
    const m = line.slice(i).match(re);
    if (m) map.set(m[1], { status: +m[4], ms: +m[5], error: m[6] ?? '' });
  }
  return map;
}
function parsePerf() {
  const map = new Map();
  const files = readdirSync(LOG_DIR)
    .filter((f) => /^laravel.*\.log$/.test(f))
    .map((f) => ({ f, t: statSync(join(LOG_DIR, f)).mtimeMs }))
    .sort((a, b) => b.t - a.t);
  const re = /\[perf\] rid=(\S+) method=(\S+) route=(\S+) status=(\d+) total_ms=([\d.]+) sql_ms=([\d.]+) queries=(\d+)(?: error=(\S+))?/;
  for (const { f } of files) {
    for (const line of readFileSync(join(LOG_DIR, f), 'utf8').split(/\r?\n/)) {
      const i = line.indexOf('[perf] rid=');
      if (i < 0) continue;
      const m = line.slice(i).match(re);
      if (m && !map.has(m[1])) {
        map.set(m[1], { status: +m[4], total: +m[5], sql: +m[6], queries: +m[7], error: m[8] ?? '' });
      }
    }
  }
  return map;
}

function pct(values, p) {
  if (!values.length) return NaN;
  const sorted = [...values].sort((a, b) => a - b);
  const idx = Math.max(0, Math.min(sorted.length - 1, Math.ceil((p / 100) * sorted.length) - 1));
  return sorted[idx];
}
const r1 = (v) => (Number.isFinite(v) ? v.toFixed(1) : '-');

// ---- phases --------------------------------------------------------------
async function createUser(phone, name) {
  const jar = new Jar();
  const su = await call('POST', '/api/auth/signup', { backend: true, body: { phone, name } });
  check('setup/signup', su, 202);
  const cid = su.json?.data?.challengeId ?? '';
  const code = otpCodeFor(cid);
  const vf = await call('POST', '/api/auth/verify', { backend: true, jar, body: { challengeId: cid, code } });
  check('setup/verify', vf, 201);
  return jar;
}

async function main() {
  // ---- cold warm-up: the first requests pay PHP/opcache compile + Next
  // route-module load (observed: first backend call >2 s → BFF fast
  // timeout → empty 500; next call queues behind php -S's single thread;
  // steady state ~100 ms from the 3rd call). Every warm call goes
  // through backendSlot() — counting a BFF-local 401 as a backend hit is
  // conservative (extra window reset), under-counting could 429 mid-run.
  console.log('warm-up — cold start…');
  await call('POST', '/api/auth/otp', { backend: true, body: { identity: newPhone(), purpose: 'login' } });
  const warmPhone = newPhone();
  const wSu = await call('POST', '/api/auth/signup', { backend: true, body: { phone: warmPhone, name: 'Perf Warmup' } });
  const wCid = wSu.json?.data?.challengeId ?? '';
  if (wCid) {
    const wCode = otpCodeFor(wCid);
    if (wCode) await call('POST', '/api/auth/verify', { backend: true, body: { challengeId: wCid, code: wCode } });
  }
  // BFF route loads (no cookie → 401 BFF-local; login warms its route
  // with a garbage challenge → backend 401, no state touched):
  const warmCalls = [
    ['POST', '/api/auth/login', { challengeId: `${randomUlid()}${randomUlid()}`, proof: '0000' }],
    ['GET', '/api/sessions', undefined],
    ['GET', '/api/settings', undefined],
    ['GET', '/api/saved', undefined],
    ['GET', '/api/mfa/status', undefined],
    ['POST', '/api/saved', { ref_type: 'puja', ref_id: randomUlid() }],
    ['POST', '/api/mfa/enroll', { type: 'totp' }],
    ['POST', '/api/mfa/confirm', { code: '000000' }],
    ['POST', '/api/mfa/verify', { code: '000000' }],
  ];
  for (const [m, p, b] of warmCalls) {
    await call(m, p, { backend: true, body: b });
  }
  console.log('warm-up done');

  // ---- setup: 4 users. #1 carries the data-session (jarA); #1/#2 are
  // known-issue identities; #3/#4 are login-redeem identities.
  const knownIds = [newPhone(), newPhone()];
  const redeemIds = [newPhone(), newPhone()];
  console.log('setup — creating 4 users (signup → verify)…');
  const jarA = await createUser(knownIds[0], 'Perf Alice');
  await createUser(knownIds[1], 'Perf Bob');
  await createUser(redeemIds[0], 'Perf Carol');
  await createUser(redeemIds[1], 'Perf Dave');
  if (failures.length) throw new Error(`setup failed: ${JSON.stringify(failures.slice(0, 3))}`);

  // ---- 1. OTP issue, signup purpose (full issue() transaction) ----------
  await sample('otp-issue-signup', N_OTP_SIGNUP, async () => {
    const r = await call('POST', '/api/auth/otp', { backend: true, body: { identity: newPhone(), purpose: 'signup' } });
    return r;
  }, 202);

  // ---- 2. OTP issue, unknown login identity (no-oracle fast path) -------
  await sample('otp-issue-login-unknown', N_OTP_UNKNOWN, async () => {
    return call('POST', '/api/auth/otp', { backend: true, body: { identity: newPhone(), purpose: 'login' } });
  }, 202);

  // ---- 3. OTP issue, known login identity (full issue() transaction) ----
  await sample('otp-issue-login-known', N_OTP_KNOWN * knownIds.length, async (i) => {
    return call('POST', '/api/auth/otp', {
      backend: true,
      body: { identity: knownIds[i < N_OTP_KNOWN ? 0 : 1], purpose: 'login' },
    });
  }, 202);

  // ---- 4. signup intent --------------------------------------------------
  await sample('signup-issue', N_SIGNUP, async () => {
    return call('POST', '/api/auth/signup', { backend: true, body: { phone: newPhone(), name: 'Perf User' } });
  }, 202);

  // ---- 5. signup verify (user creation — bcrypt) ------------------------
  await sample('signup-verify', N_VERIFY, async () => {
    const phone = newPhone();
    const su = await call('POST', '/api/auth/signup', { backend: true, body: { phone, name: 'Perf Redeem' } });
    if (su.status !== 202) { failures.push({ e: 'signup-verify/prep', status: su.status, body: su.text.slice(0, 120) }); return failedPrep('signup-verify', 'signup'); }
    const cid = su.json?.data?.challengeId ?? '';
    const code = otpCodeFor(cid);
    return call('POST', '/api/auth/verify', { backend: true, body: { challengeId: cid, code } });
  }, 201);

  // ---- 6. login redeem (challenge issue is the prep) --------------------
  await sample('login-redeem', N_LOGIN * redeemIds.length, async (i) => {
    const identity = redeemIds[i < N_LOGIN ? 0 : 1];
    const otp = await call('POST', '/api/auth/otp', { backend: true, body: { identity, purpose: 'login' } });
    if (otp.status !== 202) { failures.push({ e: 'login-redeem/prep', status: otp.status, body: otp.text.slice(0, 120) }); return failedPrep('login-redeem', 'otp'); }
    const cid = otp.json?.data?.challengeId ?? '';
    const code = otpCodeFor(cid);
    return call('POST', '/api/auth/login', { backend: true, body: { challengeId: cid, proof: code } });
  }, 200);

  // ---- 7-9. dashboard data reads ----------------------------------------
  await sample('sessions-list', N_SESSIONS, () => call('GET', '/api/sessions', { backend: true, jar: jarA }), 200);
  await sample('settings-get', N_SETTINGS, () => call('GET', '/api/settings', { backend: true, jar: jarA }), 200);
  await sample('saved-list', N_SAVED, () => call('GET', '/api/saved', { backend: true, jar: jarA }), 200);

  // ---- 10. saved add (write) --------------------------------------------
  await sample('saved-add', N_SAVED_ADD, () => call('POST', '/api/saved', {
    backend: true, jar: jarA, body: { ref_type: 'puja', ref_id: randomUlid() },
  }), [200, 201]);

  // ---- 11. mfa status read ----------------------------------------------
  await sample('mfa-status', N_MFA_STATUS, () => call('GET', '/api/mfa/status', { backend: true, jar: jarA }), 200);

  // ---- 12. MFA step-up: enroll + confirm are the prep --------------------
  console.log('mfa — enroll + confirm…');
  const enroll = await call('POST', '/api/mfa/enroll', { backend: true, jar: jarA, body: { type: 'totp' } });
  check('setup/mfa-enroll', enroll, 201);
  const otpauth = enroll.json?.data?.otpauth_url ?? '';
  const secret = otpauth ? new URL(otpauth).searchParams.get('secret') ?? '' : '';
  if (!secret) throw new Error('mfa enroll returned no otpauth url');
  const confirm = await call('POST', '/api/mfa/confirm', { backend: true, jar: jarA, body: { code: totp(secret) } });
  check('setup/mfa-confirm', confirm, 200);

  await sample('mfa-step-up', N_MFA_STEP, async () => {
    await totpAfterStep(); // fresh 30 s window per sample — replay-safe
    return call('POST', '/api/mfa/verify', { backend: true, jar: jarA, body: { code: totp(secret) } });
  }, 200);
  if (failures.length) {
    for (const f of failures) console.error(`FAIL | ${f.e} | status=${f.status} | ${f.body}`);
  }
}

// ---- report --------------------------------------------------------------
function report() {
  const upstream = parseUpstream();
  const perf = parsePerf();

  const byEndpoint = [...new Set(results.map((r) => r.e))];
  const rows = [];
  let joined = 0;
  for (const e of byEndpoint) {
    const rs = results.filter((r) => r.e === e);
    const errs = rs.filter((r) => r.s >= 400 || r.s === -1);
    const client = rs.map((r) => r.ms);
    const up = [];
    const srv = [];
    const sql = [];
    const q = [];
    const hop = [];
    for (const r of rs) {
      const u = r.rid ? upstream.get(r.rid) : undefined;
      const p = r.rid ? perf.get(r.rid) : undefined;
      if (u) { up.push(u.ms); joined += 1; }
      if (p) { srv.push(p.total); sql.push(p.sql); q.push(p.queries); }
      if (u && p) hop.push(+(r.ms - u.ms).toFixed(1)); // BFF processing + network
    }
    rows.push({
      endpoint: e,
      n: rs.length,
      err: errs.length,
      clientP50: pct(client, 50),
      clientP95: pct(client, 95),
      upP50: pct(up, 50),
      upP95: pct(up, 95),
      srvP50: pct(srv, 50),
      srvP95: pct(srv, 95),
      sqlP50: pct(sql, 50),
      qP50: pct(q, 50),
      bffP50: pct(hop, 50),
      joins: up.length,
    });
  }

  console.log('\n=== perf baseline ===');
  console.log('endpoint | n | err | client p50/p95 | bff→lar p50/p95 | srv p50/p95 | sql p50 | q p50 | bff p50');
  for (const r of rows) {
    console.log(
      `${r.endpoint} | ${r.n} | ${r.err} | ${r1(r.clientP50)}/${r1(r.clientP95)} | ${r1(r.upP50)}/${r1(r.upP95)} | ${r1(r.srvP50)}/${r1(r.srvP95)} | ${r1(r.sqlP50)} | ${r1(r.qP50)} | ${r1(r.bffP50)}`,
    );
  }
  const budgets = {
    'otp-issue-signup': N_OTP_SIGNUP,
    'otp-issue-login-unknown': N_OTP_UNKNOWN,
    'otp-issue-login-known': N_OTP_KNOWN * 2,
    'signup-issue': N_SIGNUP,
    'signup-verify': N_VERIFY,
    'login-redeem': N_LOGIN * 2,
    'sessions-list': N_SESSIONS,
    'settings-get': N_SETTINGS,
    'saved-list': N_SAVED,
    'saved-add': N_SAVED_ADD,
    'mfa-status': N_MFA_STATUS,
    'mfa-step-up': N_MFA_STEP,
  };
  const underBudget = Object.entries(budgets)
    .filter(([e, n]) => (results.filter((r) => r.e === e).length) < n)
    .map(([e]) => e);
  const coverage = results.length ? Math.round((joined / results.length) * 100) : 0;
  console.log(`samples=${results.length} errors=${failures.length} joinCoverage=${coverage}% (client↔[upstream])`);
  if (coverage < 100) console.log('WARN — some samples have no [upstream] join (rid missing? PERF_TIMING unset?)');

  writeFileSync(OUT_JSON, JSON.stringify({
    when: new Date().toISOString(),
    results,
    upstream: Object.fromEntries(upstream),
    perf: Object.fromEntries(perf),
    rows,
  }, null, 2));
  console.log(`raw samples → ${OUT_JSON}`);

  if (failures.length || underBudget.length) {
    if (underBudget.length) console.error(`under budget: ${underBudget.join(', ')}`);
    return 1;
  }
  return 0;
}

try {
  await main();
} catch (e) {
  console.error(`FATAL | ${e?.message ?? e}`);
  for (const f of failures) console.error(`FAIL | ${f.e} | status=${f.status} | ${f.body}`);
  process.exit(1);
}
process.exit(report());
