import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const customerDir = path.join(process.cwd(), 'apps', 'customer');

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

test('customer never imports web app internals', () => {
  for (const file of getAllFiles(customerDir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no apps/web import in ${file}`).not.toMatch(/apps\/web/);
    expect(content, `no @\/ alias to web in ${file}`).not.toMatch(/from\s+["']@\/(components|lib|app)\//);
  }
});

test('customer never imports infrastructure drivers directly', () => {
  for (const file of getAllFiles(customerDir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no infrastructure import in ${file}`).not.toMatch(/infrastructure\/(database|cache|payments)/);
    expect(content, `no raw db import in ${file}`).not.toMatch(/SELECT\s+.*FROM|drizzle-orm|@prisma\/client/);
  }
});

test('customer has explicit product identity', () => {
  const config = fs.readFileSync(path.join(customerDir, 'config', 'application.ts'), 'utf8');
  expect(config).toMatch(/PRODUCT_ID/);
  expect(config).toMatch(/'customer'/);
});

test('customer route IDs are stable and namespaced', () => {
  const registry = fs.readFileSync(path.join(customerDir, 'lib', 'route-registry.ts'), 'utf8');
  for (const id of ['customer.home', 'customer.bookings', 'customer.booking.detail', 'customer.profile', 'customer.settings']) {
    expect(registry).toContain(id);
  }
  const canonical = fs.readFileSync(path.join(customerDir, 'application', 'routes.ts'), 'utf8');
  for (const id of ['customer.home', 'customer.bookings', 'customer.booking.detail', 'customer.discover', 'customer.saved', 'customer.profile', 'customer.notifications', 'customer.settings', 'customer.support']) {
    expect(canonical).toContain(id);
  }
  expect(canonical).toMatch(/access/);
  expect(canonical).toMatch(/analytics/);
});
