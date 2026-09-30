import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const packagesDir = path.join(process.cwd(), 'packages');
const BUSINESS_CAPABILITIES = ['user', 'puja', 'pandit', 'availability', 'pricing', 'booking', 'search', 'recommendations'];

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  const list = fs.readdirSync(dir, { withFileTypes: true });
  for (const file of list) {
    const fullPath = path.join(dir, file.name);
    if (file.isDirectory()) results = results.concat(getAllFiles(fullPath));
    else if (file.name.endsWith('.ts') || file.name.endsWith('.tsx')) results.push(fullPath);
  }
  return results;
}

test('domain must not import infrastructure, database, or app internals', () => {
  for (const cap of BUSINESS_CAPABILITIES) {
    const domainDir = path.join(packagesDir, cap, 'src', 'domain');
    for (const file of getAllFiles(domainDir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `${cap}/domain must not import infrastructure in ${file}`).not.toMatch(/from\s+["'].*infrastructure.*["']/);
      expect(content, `${cap}/domain must not import dal in ${file}`).not.toMatch(/from\s+["'].*\bdal\b.*["']/);
      expect(content, `${cap}/domain must not import repositories in ${file}`).not.toMatch(/from\s+["'].*repositories.*["']/);
      expect(content, `${cap}/domain must not import db clients in ${file}`).not.toMatch(/from\s+["'](pg|drizzle-orm|@prisma\/client|ioredis|redis)["']/);
      expect(content, `${cap}/domain must not import apps in ${file}`).not.toMatch(/from\s+["'].*apps\/.*["']/);
      expect(content, `${cap}/domain must not use @\/ alias in ${file}`).not.toMatch(/from\s+["']@\/.*?["']/);
    }
  }
});

test('application must not import Next.js, React, or apps', () => {
  for (const cap of BUSINESS_CAPABILITIES) {
    const appDir = path.join(packagesDir, cap, 'src', 'application');
    for (const file of getAllFiles(appDir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `${cap}/application must not import next in ${file}`).not.toMatch(/from\s+["']next\/.*?["']/);
      expect(content, `${cap}/application must not import react in ${file}`).not.toMatch(/from\s+["']react["']/);
      expect(content, `${cap}/application must not import apps in ${file}`).not.toMatch(/from\s+["'].*apps\/.*["']/);
      expect(content, `${cap}/application must not use @\/ alias in ${file}`).not.toMatch(/from\s+["']@\/.*?["']/);
    }
  }
});

test('no capability may import infrastructure db directly outside adapters', () => {
  for (const cap of BUSINESS_CAPABILITIES) {
    for (const layer of ['domain', 'application', 'contracts', 'ports']) {
      const dir = path.join(packagesDir, cap, 'src', layer);
      for (const file of getAllFiles(dir)) {
        const content = fs.readFileSync(file, 'utf8');
        expect(content, `${cap}/${layer} must not import db in ${file}`).not.toMatch(/from\s+["'].*infrastructure\/db.*["']/);
      }
    }
  }
});
