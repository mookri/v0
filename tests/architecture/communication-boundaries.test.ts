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

const SDKS = ['twilio', 'nodemailer', 'firebase-admin', 'firebase/', 'aws-sdk', '@aws-sdk', 'onesignal'];

test('no business package imports provider SDKs', () => {
  for (const pkg of ['booking', 'payment', 'identity', 'user', 'puja']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      for (const sdk of SDKS) expect(content, `no ${sdk} in ${file}`).not.toContain(sdk);
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
  }
});
