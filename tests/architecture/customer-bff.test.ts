import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const apiDir = path.join(process.cwd(), 'apps', 'customer', 'app', 'api');

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) results = results.concat(getAllFiles(full));
    else if (e.name === 'route.ts') results.push(full);
  }
  return results;
}

// OAuth/OIDC protocol endpoints (ADR-0020/0021 federation): their
// response shapes are fixed by RFC 6749 / OIDC — a 302 handoff carries
// no body to envelope, and the back-channel logout receiver must
// answer a bare 204/400 per spec (never which axis failed). They cannot
// speak the app's error envelope or import an application use case;
// everything else must (amended 2026-10-17 when the back-channel
// receiver landed). The RP-initiated logout start/landing routes join
// the same doctrine: start is a 307 browser hand-off to the OP
// end-session (no body, like authorize), landing is a browser-facing
// HTML acknowledgment page (a JSON envelope is impossible there —
// the browser arrives by top-level navigation). The security checks
// below (no raw db, no provider sdk, no cross-app import) still apply
// to every route.
const PROTOCOL_ROUTES = [
  path.join('apps', 'customer', 'app', 'api', 'auth', 'authorize', 'route.ts'),
  path.join('apps', 'customer', 'app', 'api', 'auth', 'backchannel-logout', 'route.ts'),
  path.join('apps', 'customer', 'app', 'api', 'auth', 'logout', 'start', 'route.ts'),
  path.join('apps', 'customer', 'app', 'api', 'auth', 'logout', 'landing', 'route.ts'),
];
const isProtocolRoute = (file: string): boolean =>
  PROTOCOL_ROUTES.some((p) => path.normalize(file).endsWith(p));

test('every BFF endpoint maps to a customer use case, never raw db', () => {
  const files = getAllFiles(apiDir);
  expect(files.length).toBeGreaterThan(0);
  for (const file of files) {
    const content = fs.readFileSync(file, 'utf8');
    if (!isProtocolRoute(file)) {
      expect(content, `usecase import in ${file}`).toMatch(/application\//);
    }
    expect(content, `no raw db in ${file}`).not.toMatch(/drizzle-orm|@prisma\/client|SELECT\s+.*FROM/);
    expect(content, `no provider sdk in ${file}`).not.toMatch(/from\s+["'](pg|ioredis|razorpay|stripe)["']/);
    expect(content, `no apps\/web in ${file}`).not.toMatch(/apps\/web/);
  }
});

test('BFF responses use the shared error envelope', () => {
  for (const file of getAllFiles(apiDir)) {
    if (isProtocolRoute(file)) continue;
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `envelope in ${file}`).toMatch(/api-envelope/);
  }
});
