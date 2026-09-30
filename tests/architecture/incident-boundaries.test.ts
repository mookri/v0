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

test('incident domain stays framework- and infra-free', () => {
  const dir = path.join(process.cwd(), 'packages', 'incident', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no framework in ${file}`).not.toMatch(/from\s+["'](react|next(\/.*)?)["']/);
    expect(content, `no db/sdk in ${file}`).not.toMatch(/drizzle-orm|@prisma\/client|ioredis|child_process|exec\(/);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO|UPDATE\s+\w+\s+SET/i);
  }
});

test('incident never bypasses capability authorization', () => {
  const dir = path.join(process.cwd(), 'packages', 'incident', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no god mode in ${file}`).not.toMatch(/SUPER_ADMIN|bypass.*auth|skipAuthorization/i);
  }
});
