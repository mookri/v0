import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const packagesDir = path.join(process.cwd(), 'packages');

test('packages must not import from apps', () => {
  const pkgs = fs.readdirSync(packagesDir);
  for (const pkg of pkgs) {
    const pkgPath = path.join(packagesDir, pkg);
    if (!fs.statSync(pkgPath).isDirectory()) continue;
    
    // search src for apps imports
    const srcDir = path.join(pkgPath, 'src');
    if (!fs.existsSync(srcDir)) continue;
    
    const files = fs.readdirSync(srcDir, { recursive: true });
    for (const file of files) {
      if (typeof file !== 'string') continue;
      if (!file.endsWith('.ts') && !file.endsWith('.tsx')) continue;
      
      const filePath = path.join(srcDir, file);
      const content = fs.readFileSync(filePath, 'utf8');
      
      // Verify no import from @/ or apps/
      expect(content).not.toMatch(/from\s+["']@\/.*?["']/);
      expect(content).not.toMatch(/from\s+["'](?:\.\.\/)+apps\/.*?["']/);
    }
  }
});

test('contracts package must not depend on other navdhya packages', () => {
  const pkgJsonPath = path.join(packagesDir, 'contracts', 'package.json');
  if (!fs.existsSync(pkgJsonPath)) return;
  const pkg = JSON.parse(fs.readFileSync(pkgJsonPath, 'utf8'));
  const allDeps = { ...pkg.dependencies, ...pkg.devDependencies };
  for (const dep of Object.keys(allDeps)) {
    expect(dep).not.toMatch(/^@navdhya\//);
  }
});

test('design-system must only import from contracts', () => {
  const pkgJsonPath = path.join(packagesDir, 'design-system', 'package.json');
  if (!fs.existsSync(pkgJsonPath)) return;
  const pkg = JSON.parse(fs.readFileSync(pkgJsonPath, 'utf8'));
  const allDeps = { ...pkg.dependencies, ...pkg.devDependencies };
  for (const dep of Object.keys(allDeps)) {
    if (dep.startsWith('@navdhya/')) {
      expect(dep).toBe('@navdhya/contracts');
    }
  }
});
