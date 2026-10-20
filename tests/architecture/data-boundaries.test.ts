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

for (const pkg of ['events', 'analytics', 'experimentation', 'attribution']) {
  test(`${pkg} stays framework-neutral`, () => {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
      expect(content, `no next in ${file}`).not.toMatch(/from\s+["']next(\/.*)?["']/);
    }
  });
}

test('business capabilities never depend on analytics warehouse', () => {
  for (const pkg of ['booking', 'puja', 'payment', 'user']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    if (!fs.existsSync(dir)) continue;
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no analytics import in ${file}`).not.toMatch(/@navdhya\/analytics/);
    }
  }
});

test('analytics never authorizes or mutates business state', () => {
  const dir = path.join(process.cwd(), 'packages', 'analytics', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no booking mutation in ${file}`).not.toMatch(/booking.*status\s*=/i);
    expect(content, `no allow\/deny in ${file}`).not.toMatch(/allowed\s*:\s*true|authorize\(/);
  }
});
