import { defineConfig, type Plugin } from 'vitest/config';
import * as fs from 'node:fs';
import * as path from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * Dashboard test runner config (vitest, per the package `test`
 * script). The repo's root vitest config only includes root
 * `tests/**`, so this app carries its own — with the same alias
 * doctrine: `@/` resolves to the app root, deep `@navdhya/<pkg>/…`
 * imports resolve into `packages/<pkg>/…` (fs-based, matching how the
 * root config pins them).
 */

const appRoot = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(appRoot, '..', '..');
const repoPosix = repoRoot.replace(/\\/g, '/');

const pkgNames: string[] = fs
  .readdirSync(path.join(repoRoot, 'packages'), { withFileTypes: true })
  .filter((e) => e.isDirectory())
  .map((e) => e.name);

// Deep @navdhya/<pkg>/… -> packages/<pkg>/… (RegExp with capture groups;
// string finds are exact-only in this alias plugin version).
const packageDeepAlias = {
  find: /^@navdhya\/([^/]+)\/(.+)$/,
  replacement: `${repoPosix}/packages/$1/$2`,
};

// @navdhya/<pkg> (public root only) -> packages/<pkg>/src/index.ts
const packageAliases = pkgNames.map((name) => ({
  find: `@navdhya/${name}`,
  replacement: path.join(repoRoot, 'packages', name, 'src', 'index.ts'),
}));

function tryFile(base: string, rest: string): string | null {
  for (const ext of ['.ts', '.tsx', '.js', '']) {
    const candidate = path.join(base, rest + ext);
    if (fs.existsSync(candidate) && fs.statSync(candidate).isFile()) return candidate;
  }
  const indexTs = path.join(base, rest, 'index.ts');
  if (fs.existsSync(indexTs)) return indexTs;
  return null;
}

function atLocalPlugin(): Plugin {
  return {
    name: 'dashboard-at-local-alias',
    enforce: 'pre',
    resolveId(source) {
      let m: RegExpMatchArray | null;
      if ((m = source.match(/^@navdhya\/([^/]+)\/(.+)$/))) {
        return tryFile(path.join(repoRoot, 'packages', m[1]!), m[2]!);
      }
      if (source.startsWith('@/')) {
        return tryFile(appRoot, source.slice(2));
      }
      return null;
    },
  };
}

export default defineConfig({
  plugins: [atLocalPlugin()],
  resolve: {
    alias: [packageDeepAlias, ...packageAliases],
  },
  test: {
    environment: 'node',
    include: ['app/**/*.test.ts', 'server/**/*.test.ts', 'hooks/**/*.test.ts', 'tests/**/*.test.ts'],
  },
});
