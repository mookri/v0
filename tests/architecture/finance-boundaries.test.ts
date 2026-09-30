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

test('finance uses exact money and no framework/infra imports', () => {
  const dir = path.join(process.cwd(), 'packages', 'finance', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no react/next in ${file}`).not.toMatch(/from\s+["'](react|next(\/.*)?)["']/);
    expect(content, `no sdk in ${file}`).not.toMatch(/drizzle-orm|@prisma\/client|razorpay|stripe|twilio/);
    expect(content, `no float money in ${file}`).not.toMatch(/amount:\s*number/);
    expect(content, `no balance writes in ${file}`).not.toMatch(/\.balance\s*(\+=|-=|=)/);
  }
});

test('no other package mutates finance ledger', () => {
  for (const pkg of ['booking', 'payment', 'support', 'partner']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    if (!fs.existsSync(dir)) continue;
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no finance import in ${file}`).not.toMatch(/@navdhya\/finance/);
    }
  }
});
