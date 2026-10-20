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

test('geography stays provider-neutral and framework-free', () => {
  const dir = path.join(process.cwd(), 'packages', 'geography', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no map sdk in ${file}`).not.toMatch(/google\.maps|mapbox|@googlemaps/i);
    expect(content, `no postgis in ${file}`).not.toMatch(/postgis|ST_Contains|Geometry\(/);
    expect(content, `no react/next in ${file}`).not.toMatch(/from\s+["'](react|next(\/.*)?)["']/);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO/i);
  }
});

test('no capability hardcodes city coverage or raw geo SQL', () => {
  for (const pkg of ['booking', 'pricing', 'search', 'seo', 'partner']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    if (!fs.existsSync(dir)) continue;
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no city list in ${file}`).not.toMatch(/\["Delhi",\s*"Gurgaon"/);
      expect(content, `no polygon sql in ${file}`).not.toMatch(/ST_Within|ST_Contains/i);
    }
  }
});
