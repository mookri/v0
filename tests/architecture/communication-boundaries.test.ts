import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (
        e.name === 'node_modules' ||
        e.name === '.next' ||
        e.name === '.turbo' ||
        e.name === '.swc' ||
        e.name === 'dist' ||
        e.name === 'build' ||
        e.name === '.git'
      )
        continue;
      results = results.concat(getAllFiles(full));
    } else if (e.name.endsWith('.ts') || e.name.endsWith('.tsx')) results.push(full);
  }
  return results;
}

const SDKS = ['twilio', 'nodemailer', 'firebase-admin', 'firebase', 'aws-sdk', '@aws-sdk', 'onesignal'];

test('no business package imports provider SDKs', () => {
  for (const pkg of ['booking', 'payment', 'identity', 'user', 'puja']) {
    const dir = path.join(process.cwd(), 'packages', pkg, 'src');
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      for (const sdk of [...SDKS, 'firebase/']) expect(content, `no ${sdk} in ${file}`).not.toContain(sdk);
    }
  }
});

test('communication core holds no business mutations or db access', () => {
  const dir = path.join(process.cwd(), 'packages', 'communication', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no booking mutation in ${file}`).not.toMatch(/booking.*status\s*=/i);
    expect(content, `no sql in ${file}`).not.toMatch(/SELECT\s+.*FROM|INSERT\s+INTO/i);
    expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
  }
});

test('frontends have no provider access and zero firebase traces', () => {
  for (const app of ['customer', 'web', 'partner', 'dashboard']) {
    const appDir = path.join(process.cwd(), 'apps', app);
    if (!fs.existsSync(appDir)) continue;
    for (const file of getAllFiles(appDir)) {
      const content = fs.readFileSync(file, 'utf8');
      for (const sdk of SDKS) expect(content, `no ${sdk} in ${file}`).not.toContain(sdk);
      expect(content, `no firebase in ${file}`).not.toContain('firebase');
    }
  }
}, 30000);

test('msg91 widget host appears only in @navdhya/otp loader', () => {
  const allowedLoader = path.normalize(path.join('packages', 'otp', 'src', 'widget', 'loader.ts'));
  const roots = [
    path.join(process.cwd(), 'apps'),
    path.join(process.cwd(), 'packages'),
  ];
  for (const root of roots) {
    for (const file of getAllFiles(root)) {
      const normalized = path.normalize(file);
      // next.config.ts: the CSP header MUST allowlist the widget's
      // host for it to load at all — a policy declaration is
      // configuration, not a second loader (amended 2026-10-17).
      if (normalized.endsWith(allowedLoader) || path.basename(normalized) === 'next.config.ts') continue;
      const content = fs.readFileSync(file, 'utf8');
      expect(content, `no verify.msg91.com in ${file}`).not.toContain('verify.msg91.com');
    }
  }
});
