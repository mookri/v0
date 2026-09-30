import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const packagesDir = path.join(process.cwd(), 'packages');
const BUSINESS = ['user', 'puja', 'pandit', 'availability', 'pricing', 'booking', 'search', 'recommendations'];

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

const PROVIDER_SDKS = ['pg', 'drizzle-orm', '@prisma/client', 'ioredis', 'redis', 'razorpay', 'stripe', 'aws-sdk', '@aws-sdk'];

test('business layers never import provider SDKs', () => {
  for (const cap of BUSINESS) {
    for (const layer of ['domain', 'application', 'contracts', 'ports']) {
      for (const file of getAllFiles(path.join(packagesDir, cap, 'src', layer))) {
        const content = fs.readFileSync(file, 'utf8');
        for (const sdk of PROVIDER_SDKS) {
          expect(content, `${cap}/${layer} must not import ${sdk} in ${file}`).not.toMatch(
            new RegExp(`from\\s+["']${sdk.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}["']`),
          );
        }
      }
    }
  }
});

test('business code never reads process.env directly', () => {
  for (const cap of BUSINESS) {
    for (const file of getAllFiles(path.join(packagesDir, cap, 'src'))) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `${cap} must not read process.env in ${file}`).not.toMatch(/process\.env/);
    }
  }
});

test('business code never imports Next cache or server-only stores', () => {
  for (const cap of BUSINESS) {
    for (const file of getAllFiles(path.join(packagesDir, cap, 'src'))) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `${cap} must not import next/cache in ${file}`).not.toMatch(/from\s+["']next\/cache["']/);
    }
  }
});

test('infrastructure implements ports, never the reverse', () => {
  const infraDir = path.join(process.cwd(), 'infrastructure');
  const files = getAllFiles(infraDir).filter((f) => !f.includes('node_modules'));
  expect(files.length).toBeGreaterThan(0);
  for (const file of files) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `infrastructure must not import apps in ${file}`).not.toMatch(/from\s+["'].*apps\/.*["']/);
  }
});
