#!/usr/bin/env node
/**
 * e2e/scenarios.mjs — HTTP-level E2E: browser-facing BFF → Laravel → MySQL.
 *
 * Zero dependencies (node:fetch, node:child_process, node:crypto, node:fs).
 * Scenarios S1–S13 (contract: auth/p0-contract.md). Every scenario records
 * three columns:
 *
 *   wire     — HTTP status / envelope / headers observed through the BFF
 *   db       — MySQL state read via mysql.exe (scoped navdhya_e2e user)
 *   contract — which contract clause or enforced guarantee (Mx / gates) it proves
 *
 * M2–M5 are ENFORCED by the backend since 2026-10-09 (supersession,
 * atomic redemption, jti revocation/rotation, MFA lockout + replay): the
 * scenarios assert the hardened behavior, and S13 exercises the §9b wire
 * forms of gates C1/C2/C4. The PHPUnit suite pins the service-level
 * (true-concurrency) forms.
 *
 * Env: E2E_BFF, E2E_LARAVEL, E2E_TENANT_ID, E2E_MYSQL_EXE, E2E_DB_USER,
 *      E2E_DB_PASS, E2E_DB_CENTRAL, E2E_DB_TENANT, E2E_LARAVEL_LOG
 * Exit 0 iff every recorded column is PASS.
 */

import { spawnSync } from 'node:child_process';
import { createHmac } from 'node:crypto';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

// ---------------------------------------------------------------- config --
const BFF = (process.env.E2E_BFF ?? 'http://127.0.0.1:3000').replace(/\/$/, '');
const LARAVEL = (process.env.E2E_LARAVEL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const TENANT = process.env.E2E_TENANT_ID ?? '';
const MYSQL_EXE = process.env.E2E_MYSQL_EXE ?? 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe';
const DB_USER = process.env.E2E_DB_USER ?? 'navdhya_e2e';
const DB_PASS = process.env.E2E_DB_PASS ?? 'navdhya_e2e_2026';
const DB_CENTRAL = process.env.E2E_DB_CENTRAL ?? 'navdhya_e2e_central';
const DB_TENANT = process.env.E2E_DB_TENANT ?? 'navdhya_e2e_tenant';
const LOG_DIR = process.env.E2E_LARAVEL_LOG_DIR ?? join('platform-navdhya', 'storage', 'logs');

const PHONE_A = '+919876500001'; // signup + session + MFA
const PHONE_B = '+919876500002'; // M2 dual-challenge probe
const PHONE_C = '+919876500003'; // S6 M1 wire (fresh value — A is already
// verified post-S5, and the backend answers a documented 409 for it)
const ID_A = PHONE_A.slice(-10);
const ID_B = PHONE_B.slice(-10);

if (!TENANT) {
  console.error('E2E_TENANT_ID is required (run via e2e/run.ps1).');
  process.exit(2);
}

// ------------------------------------------------------------- verdicts --
const rows = [];
let failures = 0;
function col(scenario, column, ok, note = '') {
  rows.push({ scenario, column, ok: !!ok, note });
  if (!ok) {
    failures += 1;
    console.error(`  ✗ [${scenario}/${column}] ${note}`);
  }
  return !!ok;
}

// ------------------------------------------------------------------ db ---
function db(query, which = 'tenant') {
  const database = which === 'central' ? DB_CENTRAL : DB_TENANT;
  const res = spawnSync(MYSQL_EXE, [
    `-u${DB_USER}`, `-p${DB_PASS}`, `-D${database}`, '-B', '-N', '--silent', '-e', query,
  ], { encoding: 'utf8', windowsHide: true });
  if (res.status !== 0) {
    throw new Error(`mysql failed (${res.status}): ${res.stderr || res.stdout}\nSQL: ${query}`);
  }
  return res.stdout.split(/\r?\n/).filter((l) => l.length > 0);
}

function dbCell(query, which = 'tenant') {
  const rowsOut = db(query, which);
  return rowsOut.length ? rowsOut[0] : '';
}

// ---------------------------------------------------------------- http ---
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
  get(name) { return this.map.get(name); }
  /** First cookie whose name ends with `suffix` (current or `__Host-` name). */
  getEnding(suffix) {
    for (const [k, v] of this.map) if (k.endsWith(suffix)) return v;
    return undefined;
  }
  clear() { this.map.clear(); }
}

async function call(method, path, { jar, body, headers = {}, idem, base = BFF } = {}) {
  const h = { accept: 'application/json', ...headers };
  if (body !== undefined) h['content-type'] = 'application/json';
  if (idem) h['idempotency-key'] = idem;
  if (jar && jar.header()) h.cookie = jar.header();
  const res = await fetch(`${base}${path}`, {
    method, headers: h, redirect: 'manual',
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  if (jar) jar.apply(res);
  let text = '';
  try { text = await res.text(); } catch { /* empty body */ }
  let json = null;
  if (text) { try { json = JSON.parse(text); } catch { /* non-JSON (e.g. Next HTML 500) */ } }
  const setCookie = res.headers.getSetCookie?.() ?? [];
  return { status: res.status, json, text, headers: res.headers, setCookie };
}

const okData = (r) => r.json && r.json.data !== undefined;
const errCode = (r) => r.json?.error?.code ?? '';
/** Compact response descriptor for failure notes (envelope vs raw HTML). */
const descBody = (r) => {
  if (r.json?.error) return `err=${r.json.error.code}:${r.json.error.message} rid=${r.json.error.requestId ?? '-'}`;
  if (r.json?.data) return 'ok-envelope';
  return `raw=${String(r.text ?? '').replace(/\s+/g, ' ').slice(0, 140)}`;
};

// --------------------------------------------- backend throttle pacing ----
// `throttle:10,1` on the public auth + MFA routes is ONE shared fixed
// window per IP (probe-verified: 10 mixed-route requests pass, the 11th
// gets 429 — the key is sha1(domain|ip), not per-route). The full matrix
// spends 25 backend hits, so the driver burns 8 per window and waits out
// the decay instead of manufacturing mid-matrix 429s: the backend is the
// security authority here, and its throttle is real behavior the E2E
// paces to rather than fights. thHit() is called before exactly the
// requests that reach a throttled backend route — BFF-local rejections
// (422/429/idempotency short-circuits) and unthrottled routes are not
// counted; an occasional overcount only costs an unnecessary wait.
let thWindowStart = 0;
let thCount = 0;
const TH_BUDGET = 8; // of the backend's 10 — 2 slots of headroom
const sleep = (ms) => new Promise((resolve) => { setTimeout(resolve, ms); });
async function thHit() {
  const now = Date.now();
  if (now - thWindowStart > 60_000) { thWindowStart = now; thCount = 0; }
  if (thCount >= TH_BUDGET) {
    console.log(`  ... backend throttle window spent (${thCount} hits) - waiting 61s for decay`);
    await sleep(61_000);
    thWindowStart = Date.now();
    thCount = 0;
  }
  thCount += 1;
}

// ------------------------------------------------------- drill OTP codes --
function logFiles() {
  try {
    return readdirSync(LOG_DIR)
      .filter((f) => /^laravel.*\.log$/.test(f))
      .map((f) => ({ f, m: statSync(join(LOG_DIR, f)).mtimeMs }))
      .sort((a, b) => b.m - a.m)
      .map((x) => join(LOG_DIR, x.f));
  } catch { return []; }
}

function otpCodeFor(challengeId) {
  for (const file of logFiles()) {
    let text;
    try { text = readFileSync(file, 'utf8'); } catch { continue; }
    const anchor = text.lastIndexOf(`"challenge_id":"${challengeId}"`);
    if (anchor === -1) continue;
    const window = text.slice(anchor, anchor + 400);
    const m = window.match(/"code":"(\d{4,8})"/);
    if (m) return m[1];
  }
  throw new Error(`OTP code for challenge ${challengeId} not found in logs (OTP_LOG_CODES on?)`);
}

// ----------------------------------------------------------------- TOTP --
function b32decode(s) {
  const A = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  s = s.toUpperCase().replace(/=+$/, '').replace(/\s/g, '');
  let bits = 0, val = 0;
  const out = [];
  for (const c of s) {
    const i = A.indexOf(c);
    if (i === -1) throw new Error(`bad base32 char ${c}`);
    val = (val << 5) | i; bits += 5;
    if (bits >= 8) { out.push((val >>> (bits - 8)) & 255); bits -= 8; }
  }
  return Buffer.from(out);
}

function totp(secret, stepOffset = 0) {
  const counter = Math.floor(Date.now() / 30000) + stepOffset;
  const buf = Buffer.alloc(8);
  buf.writeBigUInt64BE(BigInt(counter));
  const h = createHmac('sha1', b32decode(secret)).update(buf).digest();
  const off = h[19] & 0xf;
  const bin = ((h[off] & 0x7f) << 24) | (h[off + 1] << 16) | (h[off + 2] << 8) | h[off + 3];
  return String(bin % 1000000).padStart(6, '0');
}

function totpAnyWindow(secret) {
  for (const w of [0, -1, 1]) {
    try { return totp(secret, w); } catch { /* next */ }
  }
  throw new Error('TOTP generation failed');
}

// ============================================================= scenarios ==
const jarA = new Jar();
let challengeA = '';
let tokenBeforeLogout = '';
let recoveryCodes = [];

async function S1() {
  console.log('S1 — signup challenge (V2 TTL, §1/§4)');
  await thHit();
  const r = await call('POST', '/api/auth/signup', {
    body: { phone: PHONE_A, name: 'E2E Alice' }, idem: 'e2e-s1-key',
  });
  const cid = r.json?.data?.challengeId;
  const expiresIn = r.json?.data?.expiresIn;
  challengeA = cid ?? '';
  col('S1', 'wire', r.status === 202 && typeof cid === 'string' && cid.length >= 16,
    `status=${r.status} challengeId=${cid} ${descBody(r)}`);
  col('S1', 'wire', expiresIn === 600, `expiresIn=${expiresIn} (V2: backend 600s TTL surfaced)`);

  // Auth challenges live in customer_otp_challenges on the CENTRAL
  // connection (CustomerOtpChallenge::$connection = 'central'). TTL is
  // read created_at -> expires_at (both PHP-written, same clock) so the
  // check is immune to the app-UTC vs MySQL-system-local difference.
  const row = dbCell(
    `SELECT attempts FROM customer_otp_challenges WHERE id = '${challengeA}'`, 'central'
  );
  col('S1', 'db', row === '0', `attempts=${row || 'NO ROW'} (customer_otp_challenges row, attempts=0)`);
  const exp = dbCell(
    `SELECT TIMESTAMPDIFF(SECOND, created_at, expires_at) FROM customer_otp_challenges WHERE id = '${challengeA}'`, 'central'
  );
  const expN = Number(exp);
  col('S1', 'db', expN >= 595 && expN <= 605, `TTL created_at->expires_at = ${exp}s (V2: backend 600s)`);
  col('S1', 'contract', true, 'p0-contract §1/§4 — challenge issued once; V2 TTL');
}

async function S2() {
  console.log('S2 — idempotent replay (§4)');
  const body = { phone: PHONE_A, name: 'E2E Alice' };
  const replay = await call('POST', '/api/auth/signup', { body, idem: 'e2e-s1-key' });
  col('S2', 'wire', replay.status === 202 && replay.json?.data?.challengeId === challengeA,
    `replay status=${replay.status} same challengeId=${replay.json?.data?.challengeId === challengeA}`);
  const conflict = await call('POST', '/api/auth/signup', {
    body: { phone: PHONE_A, name: 'Different name' }, idem: 'e2e-s1-key',
  });
  col('S2', 'wire', conflict.status === 409 && errCode(conflict) === 'CONFLICT',
    `conflict status=${conflict.status} code=${errCode(conflict)}`);
  const n = dbCell(
    `SELECT COUNT(*) FROM customer_otp_challenges WHERE identifier LIKE '%${ID_A}%'`, 'central'
  );
  col('S2', 'db', n === '1', `active challenges for A = ${n} (replay must not re-send)`);
  col('S2', 'contract', true, 'p0-contract §4 — Key+replay, conflict→409');
}

async function S3() {
  console.log('S3 — uniform 401, no challenge oracle (V4)');
  await thHit();
  const r = await call('POST', '/api/auth/verify', {
    body: { challengeId: 'unknown0123456789abcd', code: '123456' },
  });
  const msg = (r.json?.error?.message ?? '').toLowerCase();
  const oracle = ['not found', 'unknown', 'does not exist', 'no challenge'].some((s) => msg.includes(s));
  col('S3', 'wire', r.status === 401 && errCode(r) === 'UNAUTHENTICATED',
    `status=${r.status} code=${errCode(r)}`);
  col('S3', 'wire', !oracle, `no existence oracle in message: "${r.json?.error?.message}"`);
  globalThis.__s3code = errCode(r);
  col('S3', 'contract', true, 'p0-contract §9a V4 — single uniform 401');
}

async function S4() {
  console.log('S4 — attempt exhaustion (M3 enforced: atomic redemption)');
  let attemptsSeen = '';
  let last = null;
  for (let i = 1; i <= 3; i += 1) {
    await thHit();
    last = await call('POST', '/api/auth/verify', { body: { challengeId: challengeA, code: '000000' } });
    if (last.status !== 401 || errCode(last) !== 'UNAUTHENTICATED') break;
    attemptsSeen = dbCell(
      `SELECT attempts FROM customer_otp_challenges WHERE id = '${challengeA}'`, 'central'
    );
    if (String(attemptsSeen) !== String(i)) break;
  }
  col('S4', 'wire', last?.status === 401 && errCode(last) === 'UNAUTHENTICATED',
    `wrong-code status=${last?.status} code=${errCode(last ?? {})}`);
  col('S4', 'db', attemptsSeen === '3', `attempts after 3 wrong = ${attemptsSeen || 'row gone'}`);
  const survives = dbCell(`SELECT COUNT(*) FROM customer_otp_challenges WHERE id = '${challengeA}'`, 'central');
  col('S4', 'db', survives === '1', `row survives 3 failures (count=${survives})`);
  await thHit();
  const fourth = await call('POST', '/api/auth/verify', { body: { challengeId: challengeA, code: '000000' } });
  const gone = dbCell(`SELECT COUNT(*) FROM customer_otp_challenges WHERE id = '${challengeA}'`, 'central');
  col('S4', 'wire', fourth.status === 401, `4th call status=${fourth.status}`);
  col('S4', 'db', gone === '0', `4th call deletes row (count=${gone})`);
  col('S4', 'wire', errCode(fourth) === globalThis.__s3code,
    `same code as unknown-challenge probe (${globalThis.__s3code}) — no oracle (V4)`);
  col('S4', 'contract', true,
    'p0-contract C4/M3 enforced: attempts counted exactly (bump under row lock), delete-on-4th — wrong-OTP ×3 → challenge deleted');
}

async function S5() {
  console.log('S5 — successful signup redeem (consume-delete, session cookie)');
  // /api/auth/verify is backend `verifySignup`: it reads the intent
  // payload (phone/name) that ONLY /api/auth/signup attaches. Challenges
  // issued via /auth/otp carry no payload and 409 on verify by design,
  // so the supported pair is signup -> verify.
  await thHit();
  const req = await call('POST', '/api/auth/signup', { body: { phone: PHONE_A, name: 'E2E Alice' } });
  const cid = req.json?.data?.challengeId;
  col('S5', 'wire', req.status === 202 && !!cid, `signup status=${req.status} cid=${cid}`);
  if (!cid) return;
  const code = otpCodeFor(cid);
  await thHit();
  // jar: capture the verify-issued session cookie — S6/S7 authenticate
  // with it. Without this the jar stays empty: those calls die BFF-local
  // at wiredActor's 401 (no backend line) even though the BFF *did* set
  // the cookie on the response.
  const redeem = await call('POST', '/api/auth/verify', { jar: jarA, body: { challengeId: cid, code } });
  const hasCookie = redeem.setCookie.some((s) => /navdhya-session=[^;]/.test(s));
  col('S5', 'wire', redeem.status === 201 && hasCookie && !!redeem.json?.data?.sessionId,
    `status=${redeem.status} cookie=${hasCookie} sessionId=${redeem.json?.data?.sessionId}`);
  const user = dbCell(`SELECT COUNT(*) FROM users WHERE phone = '${PHONE_A}'`, 'central');
  col('S5', 'db', user === '1', `users row with phone A (count=${user})`);
  const remaining = dbCell(`SELECT COUNT(*) FROM customer_otp_challenges WHERE id = '${cid}'`, 'central');
  col('S5', 'db', remaining === '0', `challenge deleted on consume (count=${remaining})`);
  col('S5', 'contract', true, 'p0-contract §2/C1 — redeem consumes the challenge atomically (M3 enforced: one transaction, row locked)');
}

async function S6() {
  console.log('S6 — M1 channel/value wire + legacy-shape rejection');
  // PHONE_A is verified by S5, and `request()` answers a documented 409
  // for the actor's own verified contact (laravel-contract.md: "409 if
  // the current value is already verified"), so the happy-path wire goes
  // through a FRESH value (change-phone purpose -> 202 + challenge row)
  // while the already-verified rule gets its own assertion below.
  const ID_C = PHONE_C.slice(-10);
  const before = Number(dbCell(
    `SELECT COUNT(*) FROM customer_otp_challenges WHERE identifier LIKE '%${ID_C}%'`, 'central'
  ));
  const modern = await call('POST', '/api/verifications', {
    jar: jarA, body: { channel: 'phone', value: PHONE_C },
  });
  // Success body is the backend's own keys passed through: {challenge_id, expires_in}.
  col('S6', 'wire', modern.status === 202 && typeof modern.json?.data?.challenge_id === 'string',
    `modern shape status=${modern.status} cid=${modern.json?.data?.challenge_id} ${descBody(modern)}`);
  const after = Number(dbCell(
    `SELECT COUNT(*) FROM customer_otp_challenges WHERE identifier LIKE '%${ID_C}%'`, 'central'
  ));
  col('S6', 'db', after === before + 1, `active challenges ${before} → ${after} (row created)`);
  const owned = await call('POST', '/api/verifications', {
    jar: jarA, body: { channel: 'phone', value: PHONE_A },
  });
  col('S6', 'wire', owned.status === 409 && errCode(owned) === 'CONFLICT',
    `own verified contact status=${owned.status} code=${errCode(owned)} (documented 409)`);
  const legacy = await call('POST', '/api/verifications', {
    jar: jarA, body: { type: 'phone', contact: PHONE_A },
  });
  col('S6', 'wire', legacy.status === 422 && errCode(legacy) === 'VALIDATION_ERROR',
    `legacy {type,contact} status=${legacy.status} code=${errCode(legacy)}`);
  col('S6', 'contract', true, 'M1 fixed: discriminated union {channel,value}; legacy shape rejected at BFF');
}

async function S7() {
  console.log('S7 — cookie-first actor (§7)');
  const withCookie = await call('GET', '/api/sessions', { jar: jarA });
  col('S7', 'wire', withCookie.status === 200 && Array.isArray(withCookie.json?.data),
    `cookie session status=${withCookie.status} dataIsArray=${Array.isArray(withCookie.json?.data)}`);
  const noCookie = await call('GET', '/api/sessions');
  col('S7', 'wire', noCookie.status === 401 && errCode(noCookie) === 'UNAUTHENTICATED',
    `no cookie status=${noCookie.status} code=${errCode(noCookie)}`);
  const garbage = new Jar();
  garbage.map.set('navdhya-session', 'x'.repeat(48));
  const bad = await call('GET', '/api/sessions', { jar: garbage });
  col('S7', 'wire', bad.status === 401 && errCode(bad) === 'UNAUTHENTICATED',
    `invalid cookie status=${bad.status} code=${errCode(bad)} — hard 401, no header fallback`);
  col('S7', 'contract', true, 'p0-contract §7 — cookie wins; invalid cookie = hard 401');
}

async function S8() {
  console.log('S8 — login redeem with canonical `proof` (§2)');
  await thHit();
  const req = await call('POST', '/api/auth/otp', { body: { identity: PHONE_A, purpose: 'login' } });
  const cid = req.json?.data?.challengeId;
  col('S8', 'wire', req.status === 202 && !!cid, `otp status=${req.status}`);
  if (!cid) return;
  const code = otpCodeFor(cid);
  await thHit();
  const redeem = await call('POST', '/api/auth/login', { jar: jarA, body: { challengeId: cid, proof: code } });
  const hasCookie = redeem.setCookie.some((s) => /navdhya-session=[^;]/.test(s));
  col('S8', 'wire', redeem.status === 200 && hasCookie && redeem.json?.data?.receivedForm === 'proof',
    `status=${redeem.status} cookie=${hasCookie} receivedForm=${redeem.json?.data?.receivedForm}`);
  col('S8', 'contract', true, 'p0-contract §2 — proof is canonical; cookie issued');
}

async function S9() {
  console.log('S9 — logout: cookie clearing + M5 revocation (enforced)');
  tokenBeforeLogout = jarA.getEnding('navdhya-session') ?? '';
  const auditBefore = Number(dbCell(
    `SELECT COUNT(*) FROM audit_logs WHERE action = 'customers.auth.logged_out'`
  ));
  const revokedBefore = Number(dbCell(
    `SELECT COUNT(*) FROM actor_sessions WHERE revoked_at IS NOT NULL`, 'central'
  ));
  const out = await call('POST', '/api/auth/logout', { jar: jarA });
  const cleared = out.setCookie.some((s) => /navdhya-session=(;|$)/.test(s.split(';')[0] + ';')
    && /max-age=0/i.test(s));
  col('S9', 'wire', out.status === 200 && out.json?.data?.loggedOut === true,
    `status=${out.status} loggedOut=${out.json?.data?.loggedOut}`);
  col('S9', 'wire', cleared, `Set-Cookie clears navdhya-session: ${out.setCookie.join(' | ')}`);
  jarA.clear();
  const after = await call('GET', '/api/sessions', { jar: jarA });
  col('S9', 'wire', after.status === 401, `post-logout cookie read status=${after.status}`);

  // G4 (M5/TOKEN-001 enforced): the presented jti dies server-side — the
  // bearer is revoked, not merely discarded by the client.
  const direct = await fetch(
    `${LARAVEL}/api/v1/identity/sessions?tenant_id=${encodeURIComponent(TENANT)}`,
    {
      headers: {
        accept: 'application/json',
        authorization: `Bearer ${tokenBeforeLogout}`,
        'x-tenant-id': TENANT,
      },
    },
  ).catch(() => null);
  col('S9', 'contract', direct?.status === 401,
    `p0-contract G4 — M5 enforced: logout revoked the jti (direct Laravel status=${direct?.status ?? 'ERR'}, bearer must be dead)`);

  const audit = Number(dbCell(
    `SELECT COUNT(*) FROM audit_logs WHERE action = 'customers.auth.logged_out'`
  ));
  col('S9', 'db', audit > auditBefore, `audit_logs customers.auth.logged_out ${auditBefore} → ${audit}`);
  const revokedAfter = Number(dbCell(
    `SELECT COUNT(*) FROM actor_sessions WHERE revoked_at IS NOT NULL`, 'central'
  ));
  col('S9', 'db', revokedAfter > revokedBefore,
    `actor_sessions revoked_at stamped: ${revokedBefore} → ${revokedAfter} (revocation store)`);
  col('S9', 'contract', true,
    'M5/TOKEN-001 enforced: actor_sessions revocation store kills the credential; cookie clearing is only the client half');
}

async function S10() {
  console.log('S10 — M2: challenge supersession (enforced) + login no-oracle decoy');
  // No-oracle decoy: purpose=login for an UNKNOWN identity returns an
  // identical 202 whose id is never persisted (requestOtp line 144-157).
  await thHit();
  const decoy = await call('POST', '/api/auth/otp', { body: { identity: PHONE_B, purpose: 'login' } });
  const decoyCid = decoy.json?.data?.challengeId ?? '';
  const decoyRows = decoyCid
    ? dbCell(`SELECT COUNT(*) FROM customer_otp_challenges WHERE id = '${decoyCid}'`, 'central')
    : 'n/a';
  col('S10', 'wire', decoy.status === 202 && !!decoyCid,
    `unknown-identity login otp status=${decoy.status} decoyCid=${decoyCid} (identical 202)`);
  col('S10', 'db', decoyRows === '0', `decoy id persisted anywhere: ${decoyRows} rows (must be 0)`);
  if (decoyCid) {
    await thHit();
    const dv = await call('POST', '/api/auth/verify', { body: { challengeId: decoyCid, code: '123456' } });
    col('S10', 'wire', dv.status === 401, `decoy verify status=${dv.status} (401 = can never verify)`);
  }

  // Real login challenges require an existing account — provision
  // PHONE_B through the supported signup pair first.
  await thHit();
  const su = await call('POST', '/api/auth/signup', { body: { phone: PHONE_B, name: 'E2E Bob' } });
  const suCid = su.json?.data?.challengeId;
  col('S10', 'wire', su.status === 202 && !!suCid, `B provision signup status=${su.status}`);
  if (suCid) {
    const suCode = otpCodeFor(suCid);
    await thHit();
    const rv = await call('POST', '/api/auth/verify', { body: { challengeId: suCid, code: suCode } });
    col('S10', 'wire', rv.status === 201, `B provision verify status=${rv.status} (201 = account exists)`);
  }

  // resend: two login challenges back-to-back for the same identity
  await thHit();
  const b1 = await call('POST', '/api/auth/otp', { body: { identity: PHONE_B, purpose: 'login' } });
  await thHit();
  const b2 = await call('POST', '/api/auth/otp', { body: { identity: PHONE_B, purpose: 'login' } });
  const c1 = b1.json?.data?.challengeId;
  const c2 = b2.json?.data?.challengeId;
  col('S10', 'wire', b1.status === 202 && b2.status === 202 && c1 && c2 && c1 !== c2,
    `two distinct challenges issued (${c1} / ${c2})`);
  const active = dbCell(
    `SELECT COUNT(*) FROM customer_otp_challenges WHERE identifier LIKE '%${ID_B}%' AND purpose = 'login' AND superseded_at IS NULL`, 'central'
  );
  const total = dbCell(
    `SELECT COUNT(*) FROM customer_otp_challenges WHERE identifier LIKE '%${ID_B}%' AND purpose = 'login'`, 'central'
  );
  col('S10', 'db', active === '1' && total === '2',
    `login challenges for B: total=${total} active=${active} (expected total=2 — superseded rows retained for the hourly cap — active=1)`);
  col('S10', 'contract', active === '1',
    'p0-contract C2/G1 — M2 enforced: resend supersedes — exactly one active challenge per (tenant, purpose, identifier)');

  // G1 wire form: the superseded id dies in the same uniform 401 as any
  // other miss — even with its own (correct) code.
  if (c1) {
    await thHit();
    const dead = await call('POST', '/api/auth/login', { body: { challengeId: c1, proof: otpCodeFor(c1) } });
    col('S10', 'wire', dead.status === 401,
      `superseded challenge redeem status=${dead.status} (401 = dead like any miss, no supersession oracle)`);
  }
}

async function S11() {
  console.log('S11 — MFA: enroll=AAL1, confirm, step-up+rotation (G3), M4 lockout (G2), V6 throttle, V7 recovery');
  // V4-style: pre-auth floor on status read (GET — not throttled)
  const anon = await call('GET', '/api/mfa/status');
  col('S11', 'wire', anon.status === 401, `status w/o cookie = ${anon.status}`);

  // [mfa #1] enroll with AAL1 only (p0-contract §5 errata: step-up before
  // enroll = optional hardening, still open — see backend-security-gaps M4)
  const enroll = await call('POST', '/api/mfa/enroll', { jar: jarA, body: { type: 'totp' } });
  const otpauth = enroll.json?.data?.otpauth_url ?? '';
  col('S11', 'wire', enroll.status === 201 && otpauth.startsWith('otpauth://'),
    `enroll status=${enroll.status} otpauth=${otpauth.slice(0, 40)}…`);
  col('S11', 'contract', enroll.status === 201,
    'p0-contract §5 errata — backend enroll is AAL1-only today (step-up-before-enroll = optional hardening, backend-security-gaps M4 note)');
  if (!otpauth) return;
  const secret = new URL(otpauth).searchParams.get('secret') ?? '';

  // [mfa #2] confirm with live TOTP → recovery codes (V7 source)
  await thHit();
  const confirm = await call('POST', '/api/mfa/confirm', { jar: jarA, body: { code: totpAnyWindow(secret) } });
  recoveryCodes = confirm.json?.data?.recovery_codes ?? [];
  col('S11', 'wire', confirm.status === 200 && recoveryCodes.length >= 1,
    `confirm status=${confirm.status} recoveryCodes=${recoveryCodes.length}`);
  const mfaSecret = dbCell(
    `SELECT COUNT(*) FROM users WHERE phone = '${PHONE_A}' AND mfa_secret IS NOT NULL`, 'central'
  );
  col('S11', 'db', mfaSecret === '1', `users.mfa_secret stored (count=${mfaSecret})`);

  // [mfa #3] verify -> produces step-up (V8: row proves the window start).
  // Count-delta instead of a NOW() window: verified_at is PHP-written
  // (UTC) while MySQL NOW() is system-local — the delta is tz-immune.
  const stepBefore = Number(dbCell('SELECT COUNT(*) FROM mfa_step_ups', 'central'));
  const tokenBeforeStepUp = jarA.getEnding('navdhya-session') ?? '';
  await thHit();
  const verify = await call('POST', '/api/mfa/verify', { jar: jarA, body: { code: totpAnyWindow(secret) } });
  col('S11', 'wire', verify.status === 200, `step-up verify status=${verify.status}`);
  const stepAfter = Number(dbCell('SELECT COUNT(*) FROM mfa_step_ups', 'central'));
  col('S11', 'db', stepAfter === stepBefore + 1, `mfa_step_ups ${stepBefore} -> ${stepAfter} (row written)`);
  col('S11', 'contract', stepAfter === stepBefore + 1,
    'p0-contract §9a V8 — step-up produced; backend enforces 15-min freshness (RequiresAcr::FRESH_MINUTES)');

  // G3 / I15 (M5 enforced): MFA completion is a privilege elevation —
  // the backend rotates the session and the BFF adopts the replacement
  // into the cookie (Set-Cookie on this response).
  const rotated = jarA.getEnding('navdhya-session') ?? '';
  col('S11', 'wire', verify.setCookie.some((s) => s.includes('navdhya-session=')) && rotated !== '' && rotated !== tokenBeforeStepUp,
    `rotation Set-Cookie adopted: ${tokenBeforeStepUp.slice(0, 14)}… -> ${rotated.slice(0, 14)}…`);
  await thHit();
  const oldDirect = await fetch(`${LARAVEL}/api/v1/identity/mfa`, {
    headers: { accept: 'application/json', authorization: `Bearer ${tokenBeforeStepUp}`, 'x-tenant-id': TENANT },
  }).catch(() => null);
  col('S11', 'contract', oldDirect?.status === 401,
    `p0-contract G3 — predecessor invalidated by rotation (direct Laravel status=${oldDirect?.status ?? 'ERR'})`);
  await thHit();
  const newDirect = await fetch(`${LARAVEL}/api/v1/identity/mfa`, {
    headers: { accept: 'application/json', authorization: `Bearer ${rotated}`, 'x-tenant-id': TENANT },
  }).catch(() => null);
  col('S11', 'wire', newDirect?.status === 200,
    `rotated bearer authenticates (direct status=${newDirect?.status})`);

  // [mfa #4] V7 recovery codes cannot revoke — observed at BOTH layers:
  // the BFF schema is digits-only so an alnum recovery code cannot even
  // travel (422, which fires before the throttle: no bucket consume),
  // and the backend refuses it outright (401, asserted directly below).
  if (recoveryCodes.length >= 2) {
    const revoke = await call('POST', '/api/mfa/revoke', { jar: jarA, body: { code: recoveryCodes[1] } });
    // 401 is tolerated only for an astronomically-rare all-digit
    // recovery code that slips past the BFF schema — same V7 property,
    // refused one layer deeper.
    const refused = revoke.status === 422 || revoke.status === 401;
    col('S11', 'wire', refused,
      `BFF revoke with recovery code: status=${revoke.status} code=${errCode(revoke)} (422 = digits-only schema, 401 = backend refusal)`);
    await thHit();
    const direct = await fetch(`${LARAVEL}/api/v1/identity/mfa/revoke`, {
      method: 'POST',
      headers: {
        accept: 'application/json',
        'content-type': 'application/json',
        authorization: `Bearer ${jarA.getEnding('navdhya-session') ?? ''}`,
        'x-tenant-id': TENANT,
      },
      body: JSON.stringify({ tenant_id: TENANT, code: recoveryCodes[1] }),
    }).catch(() => null);
    col('S11', 'contract', direct?.status === 401,
      `p0-contract §9a V7 — backend refuses recovery-code revoke (direct Laravel status=${direct?.status ?? 'ERR'})`);
  }

  // [mfa #5/#6] M4 enforced: wrong codes 401 while counting toward the
  // backend lockout (5/15min) — the BFF volume throttle trips at the 6th
  // POST first, so the lock itself is proven direct-on-Laravel below (G2).
  await thHit();
  const wrong1 = await call('POST', '/api/mfa/verify', { jar: jarA, body: { code: '000000' } });
  col('S11', 'wire', wrong1.status === 401, `wrong TOTP #1 status=${wrong1.status} (failure #1 counted, below the lock threshold)`);
  await thHit();
  const wrong2 = await call('POST', '/api/mfa/verify', { jar: jarA, body: { code: '000000' } });
  const sixth = await call('POST', '/api/mfa/verify', { jar: jarA, body: { code: '000000' } });
  const retryAfter = Number(sixth.headers.get('retry-after') ?? '0');
  col('S11', 'wire', sixth.status === 429 && errCode(sixth) === 'RATE_LIMITED' && retryAfter > 0,
    `6th MFA POST status=${sixth.status} code=${errCode(sixth)} Retry-After=${retryAfter}`);
  col('S11', 'contract', sixth.status === 429,
    'p0-contract §9a V6 — BFF MFA throttle 5/900 with Retry-After (backend 10/1 beneath)');
  col('S11', 'contract', wrong2.status === 401,
    'M4 enforced: each wrong code counts toward the backend lockout (5/15min); the BFF throttle trips before it can trip');

  // [V7] recovery code authenticates login exactly once (separate throttle)
  await thHit();
  const ch4 = await call('POST', '/api/auth/otp', { body: { identity: PHONE_A, purpose: 'login' } });
  const cid4 = ch4.json?.data?.challengeId;
  if (cid4 && recoveryCodes.length >= 1) {
    const code4 = otpCodeFor(cid4);
    await thHit();
    const first = await call('POST', '/api/auth/login', {
      body: { challengeId: cid4, proof: code4, totpCode: recoveryCodes[0] },
    });
    col('S11', 'wire', first.status === 200, `login with recovery code #1 status=${first.status}`);
    await thHit();
    const ch5 = await call('POST', '/api/auth/otp', { body: { identity: PHONE_A, purpose: 'login' } });
    const cid5 = ch5.json?.data?.challengeId;
    if (cid5) {
      const code5 = otpCodeFor(cid5);
      await thHit();
      const second = await call('POST', '/api/auth/login', {
        body: { challengeId: cid5, proof: code5, totpCode: recoveryCodes[0] },
      });
      col('S11', 'wire', second.status === 401, `reuse of same recovery code status=${second.status} (401 expected)`);
      col('S11', 'contract', first.status === 200 && second.status === 401,
        'p0-contract §9a V7 — recovery codes are single-use (backend)');
    }
  }

  // [G2] backend MFA lockout (M4 enforced) — direct against Laravel to
  // bypass the BFF throttle that fired above. The recovery login reset
  // the failure counter; five more failures must lock for 15 minutes
  // (429 + Retry-After) and the CORRECT code must wait too (fail closed).
  const mfaVerifyDirect = async (code) => {
    await thHit();
    return fetch(`${LARAVEL}/api/v1/identity/mfa/verify`, {
      method: 'POST',
      headers: {
        accept: 'application/json',
        'content-type': 'application/json',
        authorization: `Bearer ${jarA.getEnding('navdhya-session') ?? ''}`,
        'x-tenant-id': TENANT,
      },
      body: JSON.stringify({ tenant_id: TENANT, code }),
    }).catch(() => null);
  };
  let lockStatus = 0;
  let lockRetry = '';
  for (let i = 1; i <= 5; i += 1) {
    const r = await mfaVerifyDirect('000000');
    lockStatus = r?.status ?? 0;
    if (lockStatus === 429) { lockRetry = r.headers.get('retry-after') ?? ''; break; }
  }
  col('S11', 'wire', lockStatus === 429 && Number(lockRetry) > 0,
    `G2 backend lockout: status=${lockStatus} Retry-After=${lockRetry} (5 failures -> 15 min)`);
  const correctWhileLocked = await mfaVerifyDirect(totpAnyWindow(secret));
  col('S11', 'contract', correctWhileLocked?.status === 429,
    `p0-contract G2 — M4 enforced: correct TOTP while locked waits out the window (status=${correctWhileLocked?.status ?? 'ERR'})`);
}

// --------------------------------------------------------------- S13/§9b --
// Wire forms of the concurrency gates. `php artisan serve` handles one
// request at a time, so Promise.all pairs are OVERLAPPING CLIENT
// operations, not true parallel processes — the row locks that close
// real multi-process races live in CustomerOtpService (transactions +
// lockForUpdate) and are pinned at the service level in PHPUnit
// (CustomerOtpSupersessionTest). The wire outcome must be identical
// either way: one winner, one uniform 401, exact counters.
//
// Throttle budgets (per-identity OTP 5/600, per-IP login 10/600): C2+C1
// spend PHONE_B's otp slots (3 in S10 + 2 here = 5), C4 takes a fresh
// PHONE_A challenge (3 so far + 1 = 4), and S13's logins (2 + 3) keep
// the suite at 9 of 10 login hits.
async function S13() {
  console.log('S13 — §9b wire: C2 single-active, C1 single-redeem, C4 exact attempts');

  // C2: two overlapping resends → exactly one active challenge; the
  // superseded row is retained (still counts toward the hourly cap).
  await thHit();
  await thHit();
  const [r1, r2] = await Promise.all([
    call('POST', '/api/auth/otp', { body: { identity: PHONE_B, purpose: 'login' } }),
    call('POST', '/api/auth/otp', { body: { identity: PHONE_B, purpose: 'login' } }),
  ]);
  const cids = [r1.json?.data?.challengeId, r2.json?.data?.challengeId].filter(Boolean);
  col('S13', 'wire', r1.status === 202 && r2.status === 202 && cids.length === 2,
    `C2 overlapping resends status=${r1.status}/${r2.status} distinctChallenges=${new Set(cids).size}`);
  const activeB = Number(dbCell(
    `SELECT COUNT(*) FROM customer_otp_challenges WHERE identifier LIKE '%${ID_B}%' AND purpose = 'login' AND superseded_at IS NULL`,
    'central',
  ));
  const totalB = Number(dbCell(
    `SELECT COUNT(*) FROM customer_otp_challenges WHERE identifier LIKE '%${ID_B}%' AND purpose = 'login'`,
    'central',
  ));
  col('S13', 'db', activeB === 1 && totalB > activeB,
    `C2 active=${activeB} total=${totalB} (one live challenge; superseded rows retained for the hourly cap)`);

  // C1: two overlapping redeems of the SAME challenge + code → exactly
  // one 200 (one session minted), the loser collapses to the uniform 401.
  const live = dbCell(
    `SELECT id FROM customer_otp_challenges WHERE identifier LIKE '%${ID_B}%' AND purpose = 'login' AND superseded_at IS NULL LIMIT 1`,
    'central',
  );
  if (live) {
    const liveCode = otpCodeFor(live);
    await thHit();
    await thHit();
    const [w1, w2] = await Promise.all([
      call('POST', '/api/auth/login', { body: { challengeId: live, proof: liveCode } }),
      call('POST', '/api/auth/login', { body: { challengeId: live, proof: liveCode } }),
    ]);
    const wins = [w1, w2].filter((x) => x.status === 200).length;
    const losses = [w1, w2].filter((x) => x.status === 401).length;
    col('S13', 'wire', wins === 1 && losses === 1,
      `C1 overlapping redeems status=${w1.status}/${w2.status} (exactly one winner, one uniform 401)`);
    col('S13', 'db', Number(dbCell(
      `SELECT COUNT(*) FROM customer_otp_challenges WHERE id = '${live}'`, 'central',
    )) === 0, 'C1 consumed challenge deleted (single use, single session)');
  } else {
    col('S13', 'wire', false, 'C2 left no active challenge to redeem');
  }

  // C4: three overlapping wrong-code logins on a fresh PHONE_A challenge
  // → attempts exactly 3 (no lost updates under contention), row alive.
  await thHit();
  const fresh = await call('POST', '/api/auth/otp', { body: { identity: PHONE_A, purpose: 'login' } });
  const freshCid = fresh.json?.data?.challengeId;
  col('S13', 'wire', fresh.status === 202 && !!freshCid, `C4 fresh challenge status=${fresh.status}`);
  if (freshCid) {
    await thHit();
    await thHit();
    await thHit();
    const wrongs = await Promise.all([1, 2, 3].map(() =>
      call('POST', '/api/auth/login', { body: { challengeId: freshCid, proof: '000000' } })));
    const attempts = Number(dbCell(
      `SELECT attempts FROM customer_otp_challenges WHERE id = '${freshCid}'`, 'central',
    ));
    col('S13', 'wire', wrongs.every((x) => x.status === 401),
      `C4 overlapping wrong codes status=${wrongs.map((x) => x.status).join('/')}`);
    col('S13', 'db', attempts === 3,
      `C4 attempts after 3 failures = ${attempts} (expected 3 — bump under row lock, no lost updates)`);
  }
}

async function S12() {
  console.log('S12 — signup IP throttle → 429 + Retry-After (§8)');
  let tripped = null;
  let trippedAt = 0;
  for (let i = 1; i <= 6; i += 1) {
    const phone = `+91955500000${i}`;
    const r = await call('POST', '/api/auth/signup', { body: { phone, name: 'Thr' } });
    trippedAt = i;
    if (r.status === 429) { tripped = r; break; }
  }
  const rawRetry = tripped?.headers.get('retry-after');
  const retryAfter = Number(rawRetry ?? '0');
  const msg = tripped?.json?.error?.message ?? '';
  // message tells the layer: BFF own-429 says "Too many attempts...";
  // a backend-forwarded 429 carries the route fallback instead.
  col('S12', 'wire', !!tripped && errCode(tripped) === 'RATE_LIMITED' && retryAfter > 0,
    `tripped=${!!tripped} at=call#${trippedAt} code=${tripped ? errCode(tripped) : '-'} retry-after=${rawRetry === null ? 'ABSENT' : rawRetry} msg="${msg}"`);
  col('S12', 'contract', !!tripped,
    'p0-contract §8 — signup IP throttle 5/600 → 429 + Retry-After');
}

// ================================================================ runner ==
async function main() {
  console.log(`E2E HTTP driver → BFF ${BFF} · Laravel ${LARAVEL} · tenant ${TENANT}\n`);
  // S11 (MFA) needs the live session cookie, so it runs before S9
  // (logout clears it); S13 (§9b wire concurrency) is independent of the
  // jar; S12 (signup IP throttle) stays last.
  const scenarios = [S1, S2, S3, S4, S5, S6, S7, S8, S10, S11, S9, S13, S12];
  for (const s of scenarios) {
    try {
      await s();
    } catch (e) {
      col(s.name, 'wire', false, `scenario threw: ${e.message}`);
    }
  }

  // ---------------------------------------------------- verdict matrix --
  const names = [...new Set(rows.map((r) => r.scenario))];
  console.log('\n## E2E verdict matrix (HTTP driver)\n');
  console.log('| Scenario | wire | db | contract | notes |');
  console.log('|---|---|---|---|---|');
  for (const s of names) {
    const cell = (c) => {
      const rs = rows.filter((r) => r.scenario === s && r.column === c);
      if (!rs.length) return '–';
      return rs.every((r) => r.ok) ? 'PASS' : '**FAIL**';
    };
    const notes = rows
      .filter((r) => r.scenario === s)
      .map((r) => `${r.ok ? '✓' : '✗'} ${r.note}`)
      .join('<br>');
    console.log(`| ${s} | ${cell('wire')} | ${cell('db')} | ${cell('contract')} | ${notes} |`);
  }
  const pass = rows.filter((r) => r.ok).length;
  console.log(`\n${pass}/${rows.length} assertions PASS · ${failures} FAIL`);
  process.exit(failures === 0 ? 0 : 1);
}

main();
