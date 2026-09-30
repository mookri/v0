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

for (const pkg of ['customer-profile', 'personalization']) {
  test(`${pkg} holds no universal customer object or framework imports`, () => {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no Customer-everything in ${file}`).not.toMatch(/Customer\s*\{[^}]*balance[^}]*points/s);
      expect(content, `no react/next in ${file}`).not.toMatch(/from\s+["'](react|next(\/.*)?)["']/);
      expect(content, `no sql/sdk in ${file}`).not.toMatch(/SELECT\s+.*FROM|drizzle-orm|razorpay|stripe/);
    }
  });
}

test('no capability derives authorization from profile signals', () => {
  for (const pkg of ['booking', 'pricing', 'support']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no lifecycle auth in ${file}`).not.toMatch(/lifecycle.*allow|segment.*allow|personalization.*allow/i);
    }
  }
});
