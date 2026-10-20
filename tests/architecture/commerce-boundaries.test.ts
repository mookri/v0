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

test('commerce holds no universal balance and no framework imports', () => {
  const dir = path.join(process.cwd(), 'packages', 'commerce', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no balance blob in ${file}`).not.toMatch(/walletBalance|rewardPoints\s*[:=]/);
    expect(content, `no react/next in ${file}`).not.toMatch(/from\s+["'](react|next(\/.*)?)["']/);
    expect(content, `no sdk in ${file}`).not.toMatch(/drizzle-orm|@prisma\/client|razorpay|stripe/);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO/i);
  }
});

test('no capability reaches into commerce stores directly', () => {
  for (const pkg of ['pricing', 'booking', 'finance', 'support']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no commerce db in ${file}`).not.toMatch(/@navdhya\/commerce\/.+\/(repository|store)/i);
    }
  }
});
