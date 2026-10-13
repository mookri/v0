import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === 'node_modules' || e.name === '.next') continue;
      results = results.concat(getAllFiles(full));
    } else if (e.name.endsWith('.ts') || e.name.endsWith('.tsx')) results.push(full);
  }
  return results;
}

const SDKS = ['twilio', 'nodemailer', 'firebase-admin', 'aws-sdk', '@aws-sdk', 'onesignal'];

/**
 * Firebase phone login (S50): the customer surface may use the client
 * SDK's entry points only — `firebase/app` (init) + `firebase/auth`
 * (phone/OTP + ID token). Every other `firebase/*` subpath (firestore,
 * functions, storage, …) stays out; `firebase-admin` stays banned
 * everywhere via SDKS; business packages keep the outright `firebase/`
 * ban (test 1 below).
 */
const CLIENT_FIREBASE_ALLOW = ['firebase/app', 'firebase/auth'];

test('no business package imports provider SDKs', () => {
  for (const pkg of ['booking', 'payment', 'identity', 'user', 'puja']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      for (const sdk of [...SDKS, 'firebase/']) expect(content, `no ${sdk} in ${file}`).not.toContain(sdk);
    }
  }
});

test('communication core holds no business mutations or db access', () => {
  const dir = path.join(process.cwd(), 'packages', 'communication', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no booking mutation in ${file}`).not.toMatch(/booking.*status\s*=/i);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO/i);
    expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
  }
});

test('customer frontend has no provider access', () => {
  for (const file of getAllFiles(path.join(process.cwd(), 'apps', 'customer'))) {
    const content = fs.readFileSync(file, 'utf8');
    for (const sdk of SDKS) expect(content, `no ${sdk} in ${file}`).not.toContain(sdk);
    // Quoted module specifiers only (import/require/dynamic import) —
    // backend *path* strings like '/customers/auth/firebase/intent'
    // never open provider access and must keep working.
    for (const spec of content.match(/['"]firebase\/[^'"]+['"]/g) ?? []) {
      expect(CLIENT_FIREBASE_ALLOW, `firebase subpath ${spec.slice(1, -1)} in ${file}`).toContain(spec.slice(1, -1));
    }
  }
});
