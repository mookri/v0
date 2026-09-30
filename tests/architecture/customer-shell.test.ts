import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const shellDir = path.join(process.cwd(), 'apps', 'customer', 'presentation');

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) results = results.concat(getAllFiles(full));
    else if (e.name.endsWith('.ts') || e.name.endsWith('.tsx')) results.push(full);
  }
  return results;
}

test('shell has no business logic or infrastructure access', () => {
  const banned = ['BookingRepository', 'PaymentGateway', 'drizzle-orm', '@prisma/client', 'ioredis', 'razorpay', 'SELECT ', 'process.env.DATABASE'];
  for (const file of getAllFiles(shellDir)) {
    const content = fs.readFileSync(file, 'utf8');
    for (const b of banned) expect(content, `no ${b} in ${file}`).not.toContain(b);
  }
});

test('shell never imports web app', () => {
  for (const file of getAllFiles(shellDir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no apps/web in ${file}`).not.toMatch(/apps\/web/);
  }
});

test('navigation model is canonical and shared', () => {
  const model = fs.readFileSync(path.join(shellDir, 'navigation', 'navigation-model.ts'), 'utf8');
  for (const id of ['customer.home', 'customer.bookings', 'customer.saved', 'customer.profile']) {
    expect(model).toContain(id);
  }
  const bottom = fs.readFileSync(path.join(shellDir, 'navigation', 'BottomNavigation.tsx'), 'utf8');
  const sidebar = fs.readFileSync(path.join(shellDir, 'navigation', 'SidebarNavigation.tsx'), 'utf8');
  expect(bottom).toMatch(/navigation-model/);
  expect(sidebar).toMatch(/navigation-model/);
});

test('no duplicate mobile application', () => {
  for (const file of getAllFiles(shellDir)) {
    expect(file).not.toMatch(/CustomerMobileApp|CustomerDesktopApp/);
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no mobile-app split in ${file}`).not.toMatch(/CustomerMobileApp|CustomerDesktopApp/);
  }
  const shell = fs.readFileSync(path.join(shellDir, 'shells', 'CustomerAppShell.tsx'), 'utf8');
  expect(shell).toMatch(/mode/);
});
