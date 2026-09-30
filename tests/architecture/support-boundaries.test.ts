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

test('support domain stays framework- and infra-free', () => {
  const dir = path.join(process.cwd(), 'packages', 'support', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no next/react in ${file}`).not.toMatch(/from\s+["'](react|next(\/.*)?)["']/);
    expect(content, `no db/sdk in ${file}`).not.toMatch(/drizzle-orm|@prisma\/client|twilio|razorpay|stripe|ioredis/);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO/i);
  }
});

test('support never mutates capability state directly', () => {
  const dir = path.join(process.cwd(), 'packages', 'support', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no status mutation in ${file}`).not.toMatch(/updateBookingStatus|markPaymentSuccessful|completeFulfillment/);
    expect(content, `no superuser bypass in ${file}`).not.toMatch(/bypass.*auth|skipAuthorization/i);
  }
});
