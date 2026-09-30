import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const apiDir = path.join(process.cwd(), 'apps', 'customer', 'app', 'api');

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) results = results.concat(getAllFiles(full));
    else if (e.name === 'route.ts') results.push(full);
  }
  return results;
}

test('every BFF endpoint maps to a customer use case, never raw db', () => {
  const files = getAllFiles(apiDir);
  expect(files.length).toBeGreaterThan(0);
  for (const file of files) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `usecase import in ${file}`).toMatch(/application\//);
    expect(content, `no raw db in ${file}`).not.toMatch(/drizzle-orm|@prisma\/client|SELECT\s+.*FROM/);
    expect(content, `no provider sdk in ${file}`).not.toMatch(/from\s+["'](pg|ioredis|razorpay|stripe)["']/);
    expect(content, `no apps\/web in ${file}`).not.toMatch(/apps\/web/);
  }
});

test('BFF responses use the shared error envelope', () => {
  for (const file of getAllFiles(apiDir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `envelope in ${file}`).toMatch(/api-envelope/);
  }
});
