import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) results = results.concat(getAllFiles(full));
    else if (e.name.endsWith('.ts')) results.push(full);
  }
  return results;
}

for (const pkg of ['content', 'seo', 'aeo']) {
  test(`${pkg} core stays framework-neutral`, () => {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
      expect(content, `no next in ${file}`).not.toMatch(/from\s+["']next(\/.*)?["']/);
    }
  });
}

test('seo never mutates canonical content stores', () => {
  const dir = path.join(process.cwd(), 'packages', 'seo', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no content mutation in ${file}`).not.toMatch(/ContentRepository|ContentSource/);
  }
});
