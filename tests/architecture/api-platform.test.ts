import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const apiDir = path.join(process.cwd(), 'apps', 'customer', 'app', 'api');

// OAuth/OIDC protocol endpoints (ADR-0020/0021 federation): RFC 6749 /
// OIDC fix their shapes — a 302 handoff has no body to envelope and
// the back-channel logout receiver answers a bare 204/400 per spec.
// The RP-initiated logout start (307 browser hand-off) and landing
// (HTML acknowledgment page) join the same doctrine: neither can carry
// a JSON envelope. They cannot speak the app envelope; everything else
// must (amended 2026-10-17). The numeric-status check still applies to
// all routes.
const PROTOCOL_ROUTES = [
  path.join('apps', 'customer', 'app', 'api', 'auth', 'authorize', 'route.ts'),
  path.join('apps', 'customer', 'app', 'api', 'auth', 'backchannel-logout', 'route.ts'),
  path.join('apps', 'customer', 'app', 'api', 'auth', 'logout', 'start', 'route.ts'),
  path.join('apps', 'customer', 'app', 'api', 'auth', 'logout', 'landing', 'route.ts'),
];
const isProtocolRoute = (file: string): boolean =>
  PROTOCOL_ROUTES.some((p) => path.normalize(file).endsWith(p));

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

test('BFF handlers share one error/status contract', () => {
  for (const file of getAllFiles(apiDir)) {
    const content = fs.readFileSync(file, 'utf8');
    if (!isProtocolRoute(file)) {
      expect(content, `envelope in ${file}`).toMatch(/api-envelope/);
    }
    expect(content, `no numeric status without code in ${file}`).not.toMatch(/status:\s*500/);
  }
});

test('api-contracts owns no infrastructure or app imports', () => {
  const dir = path.join(process.cwd(), 'packages', 'api-contracts', 'src');
  const walk = (d: string): string[] => {
    let out: string[] = [];
    for (const e of fs.readdirSync(d, { withFileTypes: true })) {
      const full = path.join(d, e.name);
      if (e.isDirectory()) out = out.concat(walk(full));
      else if (e.name.endsWith('.ts')) out.push(full);
    }
    return out;
  };
  for (const file of walk(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no infra in ${file}`).not.toMatch(/drizzle-orm|pg["']|redis|apps\//);
  }
});
