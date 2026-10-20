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

test('feedback holds no universal score or business mutation', () => {
  const dir = path.join(process.cwd(), 'packages', 'feedback', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no trustScore in ${file}`).not.toMatch(/trustScore|reputationScore\s*=/);
    expect(content, `no payout in ${file}`).not.toMatch(/payable|payout|ledger/);
    expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO/i);
  }
});

test('no capability treats rating as authorization or state', () => {
  for (const pkg of ['booking', 'assignment', 'search']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    if (!fs.existsSync(dir)) continue;
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no rating auth in ${file}`).not.toMatch(/rating\s*>=|trustScore/);
    }
  }
});
