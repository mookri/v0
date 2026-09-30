import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const appDir = path.join(process.cwd(), 'apps', 'customer', 'application');
const presDir = path.join(process.cwd(), 'apps', 'customer', 'presentation');

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) results = results.concat(getAllFiles(full));
    else if (e.name.endsWith('.ts') || e.name.endsWith('.tsx')) results.push(full);
  }
  return results;
}

test('customer application never touches React/Next/infra', () => {
  for (const file of getAllFiles(appDir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
    expect(content, `no next in ${file}`).not.toMatch(/from\s+["']next(\/.*)?["']/);
    expect(content, `no infra in ${file}`).not.toMatch(/infrastructure\//);
    expect(content, `no apps/web in ${file}`).not.toMatch(/apps\/web/);
  }
});

test('customer presentation holds no business rules or db access', () => {
  const banned = ['canCancel', 'canTransition', 'LEGAL_TRANSITIONS', 'SELECT ', 'drizzle-orm', 'BookingRepository'];
  for (const file of getAllFiles(presDir)) {
    const content = fs.readFileSync(file, 'utf8');
    for (const b of banned) expect(content, `no ${b} in ${file}`).not.toContain(b);
  }
});
