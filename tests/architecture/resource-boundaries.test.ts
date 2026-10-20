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

test('resource domain stays provider- and framework-free', () => {
  const dir = path.join(process.cwd(), 'packages', 'resource-management', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no sdk in ${file}`).not.toMatch(/aws-sdk|@aws-sdk|drizzle-orm|@prisma\/client|ioredis/);
    expect(content, `no react/next in ${file}`).not.toMatch(/from\s+["'](react|next(\/.*)?)["']/);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO/i);
    expect(content, `no ui stock checks in ${file}`).not.toMatch(/if\s*\(\s*stock\s*</);
  }
});

test('no capability reaches into resource stores or recalculates stock', () => {
  for (const pkg of ['fulfillment', 'booking', 'pricing', 'finance']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no resource db in ${file}`).not.toMatch(/@navdhya\/resource-management\/.+\/(store|repository)/i);
    }
  }
});
