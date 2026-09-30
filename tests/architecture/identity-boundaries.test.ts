import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

function getAllFiles(dir: string, ext: string[] = ['.ts', '.tsx']): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === 'node_modules' || e.name === '.next') continue;
      results = results.concat(getAllFiles(full, ext));
    } else if (ext.some((x) => e.name.endsWith(x))) results.push(full);
  }
  return results;
}

test('identity never imports business capabilities', () => {
  const dir = path.join(process.cwd(), 'packages', 'identity', 'src');
  const banned = ['@navdhya/booking', '@navdhya/payment', 'booking/', 'payment', 'pricing', 'puja', 'pandit', 'ads', 'content'];
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    for (const b of banned) expect(content, `no ${b} in ${file}`).not.toContain(b);
  }
});

test('apps never import credential internals or session secrets', () => {
  for (const app of ['apps/customer', 'apps/web']) {
    for (const file of getAllFiles(path.join(process.cwd(), app))) {
      const content = fs.readFileSync(file, 'utf8');
      // Exception: server-side composition roots (apps/*/server/**) may wire
      // identity ports (verifier/stores) — that is the documented seam, never
      // a client/component import.
      const isServerComposition = file.includes(`${path.sep}server${path.sep}`);
      if (!isServerComposition) {
        expect(content, `no otp provider in ${file}`).not.toMatch(/otp-provider|CredentialVerifier/);
      }
      expect(content, `no localStorage tokens in ${file}`).not.toMatch(/localStorage\s*\.\s*(getItem|setItem)\s*\(\s*["'].*token/i);
      expect(content, `no token in url in ${file}`).not.toMatch(/access_token|refresh_token/);
    }
  }
});

test('no cross-app imports', () => {
  for (const file of getAllFiles(path.join(process.cwd(), 'apps', 'customer'))) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no apps/web in ${file}`).not.toMatch(/apps\/web/);
  }
});
