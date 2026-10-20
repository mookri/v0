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

for (const pkg of ['search', 'discovery']) {
  test(`${pkg} stays framework-neutral`, () => {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
      expect(content, `no next in ${file}`).not.toMatch(/from\s+["']next(\/.*)?["']/);
    }
  });
}

test('search never mutates business state or touches db directly', () => {
  const dir = path.join(process.cwd(), 'packages', 'search', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO|drizzle-orm|@prisma\/client/);
    expect(content, `no booking mutation in ${file}`).not.toMatch(/booking.*status\s*=/i);
  }
});

test('recommendations stay independent of search internals', () => {
  const dir = path.join(process.cwd(), 'packages', 'recommendations', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no search internals in ${file}`).not.toMatch(/@navdhya\/search\/.+/);
  }
});

test('public index holds no private data markers', () => {
  const infra = path.join(process.cwd(), 'infrastructure', 'search', 'InMemorySearchEngine.ts');
  const content = fs.readFileSync(infra, 'utf8');
  expect(content).not.toMatch(/email|phone|payment|customer.*secret/i);
});
