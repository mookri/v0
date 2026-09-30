import { defineConfig, type Plugin } from 'vitest/config';
import * as fs from 'fs';
import * as path from 'path';

const root = __dirname;
const rootPosix = root.replace(/\\/g, '/');
const pkgNames: string[] = fs
  .readdirSync(path.join(root, 'packages'), { withFileTypes: true })
  .filter((e) => e.isDirectory())
  .map((e) => e.name);

// Deep @navdhya/<pkg>/… -> packages/<pkg>/… (RegExp with capture groups;
// string finds are exact-only in this alias plugin version).
const packageDeepAlias = {
  find: /^@navdhya\/([^/]+)\/(.+)$/,
  replacement: `${rootPosix}/packages/$1/$2`,
};

// @navdhya/<pkg> (public root only) -> packages/<pkg>/src/index.ts
const packageAliases = pkgNames.map((name) => ({
  find: `@navdhya/${name}`,
  replacement: path.join(root, 'packages', name, 'src', 'index.ts'),
}));

// @/… resolves relative to the importer's nearest enclosing src/ (packages),
// app root (apps/*), infrastructure root, or tests root. Deep @navdhya/<pkg>/…,
// @infra/… and @apps/… are resolved here too (fs-based: immune to alias-plugin
// exact-match semantics under SSR externalization).
function atLocalPlugin(): Plugin {
  return {
    name: 'at-local-alias',
    enforce: 'pre',
    resolveId(source, importer) {
      const tryFile = (base: string, rest: string): string | null => {
        for (const ext of ['.ts', '.tsx', '.js', '']) {
          const candidate = path.join(base, rest + ext);
          if (fs.existsSync(candidate) && fs.statSync(candidate).isFile()) return candidate;
        }
        const indexTs = path.join(base, rest, 'index.ts');
        if (fs.existsSync(indexTs)) return indexTs;
        return null;
      };
      let m: RegExpMatchArray | null;
      if ((m = source.match(/^@navdhya\/([^/]+)\/(.+)$/))) {
        return tryFile(path.join(root, 'packages', m[1]!), m[2]!);
      }
      if ((m = source.match(/^@infra\/(.+)$/))) {
        return tryFile(path.join(root, 'infrastructure'), m[1]!);
      }
      if ((m = source.match(/^@apps\/(.+)$/))) {
        return tryFile(path.join(root, 'apps'), m[1]!);
      }
      if (!source.startsWith('@/') || !importer) return null;
      const rest = source.slice(2);
      const dir = path.dirname(importer);
      const rel = path.relative(root, dir).split(path.sep);
      let base: string | null = null;
      if (rel[0] === 'packages') {
        const srcIdx = rel.indexOf('src');
        base = srcIdx >= 0 ? path.join(root, ...rel.slice(0, srcIdx + 1)) : path.join(root, 'packages', rel[1]!, 'src');
      } else if (rel[0] === 'apps') {
        base = path.join(root, 'apps', rel[1]!);
      } else if (rel[0] === 'infrastructure') {
        base = path.join(root, 'infrastructure');
      } else if (rel[0] === 'tests') {
        base = path.join(root, 'tests');
      }
      if (!base) return null;
      for (const ext of ['.ts', '.tsx', '.js', '']) {
        const candidate = path.join(base, rest + ext);
        if (fs.existsSync(candidate) && fs.statSync(candidate).isFile()) return candidate;
        if (fs.existsSync(candidate + '.ts')) return candidate + '.ts';
      }
      const indexTs = path.join(base, rest, 'index.ts');
      if (fs.existsSync(indexTs)) return indexTs;
      return null;
    },
  };
}

const rootPosixAliasNote = rootPosix;

export default defineConfig({
  plugins: [atLocalPlugin()],
  resolve: {
    alias: [
      packageDeepAlias,
      ...packageAliases,
      { find: '@infra/', replacement: `${rootPosixAliasNote}/infrastructure/` },
      { find: '@apps/', replacement: `${rootPosixAliasNote}/apps/` },
    ],
  },
  test: {
    include: ['tests/**/*.test.ts'],
  },
  server: {
    deps: {
      inline: [/@navdhya\//, /^@infra\//, /^@apps\//],
    },
  },
});
