import { describe, test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const ROOT = process.cwd();
const APPS = ['web', 'customer', 'partner', 'dashboard'] as const;
const APP_PKG_NAMES: Record<(typeof APPS)[number], string> = {
  web: '@navdhya/web',
  customer: '@navdhya/customer',
  partner: '@navdhya/partner-app',
  dashboard: '@navdhya/dashboard',
};

const SKIP_DIRS = new Set(['node_modules', '.next', '.turbo', '.git', 'dist', 'coverage']);

function walkSourceFiles(dir: string): string[] {
  const results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (SKIP_DIRS.has(entry.name)) continue;
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      results.push(...walkSourceFiles(full));
    } else if (/\.(ts|tsx|js|jsx|mjs|cjs)$/.test(entry.name)) {
      results.push(full);
    }
  }
  return results;
}

function extractImportSpecifiers(source: string): string[] {
  const specs: string[] = [];
  const staticRe = /(?:from|import)\s+["']([^"']+)["']/g;
  const dynamicRe = /import\(\s*["']([^"']+)["']\s*\)/g;
  for (const re of [staticRe, dynamicRe]) {
    let m: RegExpExecArray | null;
    while ((m = re.exec(source)) !== null) {
      specs.push(m[1]);
    }
  }
  return specs;
}

describe('Monorepo app boundaries & framework parity (E-AP-01)', () => {
  test('all four apps pin the exact same Next.js and React versions', () => {
    const versions = APPS.map((app) => {
      const pkg = JSON.parse(
        fs.readFileSync(path.join(ROOT, 'apps', app, 'package.json'), 'utf8'),
      ) as { dependencies?: Record<string, string> };
      return {
        app,
        next: pkg.dependencies?.next,
        react: pkg.dependencies?.react,
      };
    });

    for (const v of versions) {
      expect(v.next, `${v.app} Next.js version`).toBe('16.4.2');
      expect(v.react, `${v.app} React version`).toBe('^19');
    }
  });

  test('each app declares extends: ["//"] and its own app:<name> boundary tag', () => {
    for (const app of APPS) {
      const turboPath = path.join(ROOT, 'apps', app, 'turbo.json');
      expect(fs.existsSync(turboPath), `Missing ${turboPath}`).toBe(true);
      const parsed = JSON.parse(fs.readFileSync(turboPath, 'utf8')) as {
        extends?: string[];
        tags?: string[];
      };
      expect(parsed.extends).toEqual(['//']);
      expect(parsed.tags).toContain(`app:${app}`);
    }
  });

  test('no app imports from another app via workspace alias or relative path', () => {
    const violations: string[] = [];

    for (const app of APPS) {
      const appDir = path.join(ROOT, 'apps', app);
      const otherApps = APPS.filter((a) => a !== app);

      for (const file of walkSourceFiles(appDir)) {
        const content = fs.readFileSync(file, 'utf8');
        for (const spec of extractImportSpecifiers(content)) {
          for (const other of otherApps) {
            const otherPkg = APP_PKG_NAMES[other];
            if (spec === otherPkg || spec.startsWith(`${otherPkg}/`)) {
              violations.push(`${path.relative(ROOT, file)} imports ${spec}`);
            }
            if (spec.startsWith('.')) {
              const resolved = path.resolve(path.dirname(file), spec);
              const otherRoot = path.join(ROOT, 'apps', other) + path.sep;
              if (resolved.startsWith(otherRoot)) {
                violations.push(`${path.relative(ROOT, file)} resolves across app boundary to ${spec}`);
              }
            }
          }
        }
      }
    }

    expect(violations).toEqual([]);
  });

  test('no shared package imports from any app', () => {
    const violations: string[] = [];
    const packagesDir = path.join(ROOT, 'packages');

    for (const pkg of fs.readdirSync(packagesDir, { withFileTypes: true })) {
      if (!pkg.isDirectory()) continue;
      const pkgRoot = path.join(packagesDir, pkg.name);
      for (const file of walkSourceFiles(pkgRoot)) {
        const content = fs.readFileSync(file, 'utf8');
        for (const spec of extractImportSpecifiers(content)) {
          for (const app of APPS) {
            const appPkg = APP_PKG_NAMES[app];
            if (spec === appPkg || spec.startsWith(`${appPkg}/`)) {
              violations.push(`${path.relative(ROOT, file)} imports app package ${spec}`);
            }
          }
          if (spec.startsWith('.')) {
            const resolved = path.resolve(path.dirname(file), spec);
            const appsRoot = path.join(ROOT, 'apps') + path.sep;
            if (resolved.startsWith(appsRoot)) {
              violations.push(`${path.relative(ROOT, file)} resolves into apps/ via ${spec}`);
            }
          }
        }
      }
    }

    expect(violations).toEqual([]);
  });

  test('frontend apps do not import deprecated client-side tax/quote/ledger/availability engines (A-19)', () => {
    const forbidden = [
      /@navdhya\/pricing\/src\/(tax|engine|promotions)/,
      /@navdhya\/finance\/src\/(domain|application)/,
      /@navdhya\/availability\/src\/domain/,
    ];
    const violations: string[] = [];

    for (const app of APPS) {
      for (const file of walkSourceFiles(path.join(ROOT, 'apps', app))) {
        const content = fs.readFileSync(file, 'utf8');
        for (const spec of extractImportSpecifiers(content)) {
          if (forbidden.some((re) => re.test(spec))) {
            violations.push(`${path.relative(ROOT, file)} imports forbidden domain logic ${spec}`);
          }
        }
      }
    }

    expect(violations).toEqual([]);
  });
});

