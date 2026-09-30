import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const ROOTS = ['packages', 'apps', 'infrastructure', 'tests', 'workers', 'tooling'];

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === 'node_modules' || e.name === '.next' || e.name === '.turbo') continue;
      results = results.concat(getAllFiles(full));
    } else if (e.name.endsWith('.ts') || e.name.endsWith('.tsx') || e.name.endsWith('.js')) results.push(full);
  }
  return results;
}

test('no parent-directory imports anywhere: use @ aliases', () => {
  const violations: string[] = [];
  for (const root of ROOTS) {
    const dir = path.join(process.cwd(), root);
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      const importRe = /(?:import|export)[^'"]*?from\s+["']([^"']+)["']/g;
      const sideEffectRe = /import\s+["']([^"']+)["']/g;
      let m: RegExpExecArray | null;
      const specs = new Set<string>();
      while ((m = importRe.exec(content)) !== null) specs.add(m[1]!);
      while ((m = sideEffectRe.exec(content)) !== null) specs.add(m[1]!);
      for (const spec of specs) {
        if (spec === '.' || spec === '..' || spec.startsWith('../') || spec.includes('/../') || spec.endsWith('/..')) {
          violations.push(`${file} -> ${spec}`);
        }
      }
    }
  }
  expect(violations, `parent imports forbidden, use @ aliases:\n${violations.slice(0, 20).join('\n')}`).toEqual([]);
});
