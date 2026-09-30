import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

// Edge-session ADR (docs/architecture/edge-session.md): the edge entrypoint
// and its imports must never pull identity, session contracts, or stores.
const EDGE_ROOTS = [
  path.join(process.cwd(), 'apps', 'web', 'proxy.ts'),
  path.join(process.cwd(), 'apps', 'web', 'lib', 'strangler'),
];

const BANNED = [
  '@navdhya/identity',
  '@navdhya/contracts/src/payment',
  '@navdhya/contracts/src/request-context',
  'infrastructure/',
  'packages/identity',
  'packages/contracts/src/request-context',
];

function getAllFiles(dir: string): string[] {
  if (!fs.existsSync(dir)) return [];
  if (fs.statSync(dir).isFile()) return dir.endsWith('.ts') ? [dir] : [];
  let out: string[] = [];
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) out = out.concat(getAllFiles(full));
    else if (e.name.endsWith('.ts')) out.push(full);
  }
  return out;
}

test('edge entrypoint imports no identity/session/store modules', { timeout: 60000 }, () => {
  const violations: string[] = [];
  for (const root of EDGE_ROOTS) {
    for (const file of getAllFiles(root)) {
      const content = fs.readFileSync(file, 'utf8');
      for (const banned of BANNED) {
        if (content.includes(banned)) violations.push(`${file} imports ${banned}`);
      }
    }
  }
  expect(violations).toEqual([]);
});
