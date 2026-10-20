import { defineConfig, type Plugin } from 'vitest/config';
import * as fs from 'node:fs';
import * as path from 'node:path';
import { fileURLToPath } from 'node:url';

const appRoot = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(appRoot, '..', '..');
const repoPosix = repoRoot.replace(/\\/g, '/');

const pkgNames: string[] = fs
  .readdirSync(path.join(repoRoot, 'packages'), { withFileTypes: true })
  .filter((e) => e.isDirectory())
  .map((e) => e.name);

const packageDeepAlias = {
  find: /^@navdhya\/([^/]+)\/(.+)$/,
  replacement: `${repoPosix}/packages/$1/$2`,
};

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
    name: 'customer-at-local-alias',
    enforce: 'pre',
    resolveId(source) {
      if (source.startsWith('@/')) {
        return tryFile(appRoot, source.slice(2));
      }
      let m: RegExpMatchArray | null;
      if ((m = source.match(/^@navdhya\/([^/]+)\/(.+)$/))) {
        return tryFile(path.join(repoRoot, 'packages', m[1]!), m[2]!);
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
    include: ['application/**/*.test.ts', 'app/**/*.test.ts', 'tests/**/*.test.ts'],
  },
});
