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

for (const pkg of ['fulfillment', 'scheduling']) {
  test(`${pkg} stays framework-neutral`, () => {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
      expect(content, `no next in ${file}`).not.toMatch(/from\s+["']next(\/.*)?["']/);
    }
  });
}

test('fulfillment never calls providers or mutates booking ledgers', () => {
  const dir = path.join(process.cwd(), 'packages', 'fulfillment', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no sdk in ${file}`).not.toMatch(/twilio|nodemailer|firebase|razorpay|stripe/);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO/i);
  }
});

test('booking stays free of fulfillment internals', () => {
  const dir = path.join(process.cwd(), 'packages', 'booking', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no fulfillment import in ${file}`).not.toMatch(/@navdhya\/fulfillment|fulfillment/);
  }
});
