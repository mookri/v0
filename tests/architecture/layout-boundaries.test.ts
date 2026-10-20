import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const engineDir = path.join(process.cwd(), 'packages', 'layout-engine', 'src');

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) results = results.concat(getAllFiles(full));
    else if (e.name.endsWith('.ts')) results.push(full);
  }
  return results;
}

test('layout-engine core stays framework-neutral (no React/Next)', () => {
  for (const file of getAllFiles(engineDir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
    expect(content, `no next in ${file}`).not.toMatch(/from\s+["']next(\/.*)?["']/);
  }
});

test('layout-engine never imports business capabilities or infrastructure', () => {
  const banned = ['@navdhya/booking', '@navdhya/payment', '@navdhya/pandit', '@navdhya/puja', 'infrastructure/', 'pg', 'redis', 'razorpay'];
  for (const file of getAllFiles(engineDir)) {
    const content = fs.readFileSync(file, 'utf8');
    for (const b of banned) {
      expect(content, `no ${b} in ${file}`).not.toContain(b);
    }
    expect(content, `no node:crypto in ${file}`).not.toMatch(/node:crypto/);
  }
});

test('layout-engine never imports business UI shells', () => {
  for (const file of getAllFiles(engineDir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no Shell component import in ${file}`).not.toMatch(/Shell\.tsx|components\/dlo-x\/shells/);
  }
});
