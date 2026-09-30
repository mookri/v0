import { defineConfig, type Plugin } from 'vitest/config';
import * as fs from 'fs';
import * as path from 'path';

const root = __dirname;
const pkgNames: string[] = fs
  .readdirSync(path.join(root, 'packages'), { withFileTypes: true })
  .filter((e) => e.isDirectory())
  .map((e) => e.name);

// @navdhya/<pkg> (public root only) -> packages/<pkg>/src/index.ts
const packageAliases = pkgNames.map((name) => ({
  find: new RegExp(`^@navdhya/${name}$`),
  replacement: path.join(root, 'packages', name, 'src', 'index.ts'),
}));

// @/… resolves relative to the importer's nearest enclosing src/ (packages),
// app root (apps/*), infrastructure root, or tests root.
function atLocalPlugin(): Plugin {
  return {
    name: 'at-local-alias',
    enforce: 'pre',
    resolveId(source, importer) {
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

const rootPosix = root.replace(/\\/g, '/');

export default defineConfig({
  plugins: [atLocalPlugin()],
  resolve: {
    alias: [
      ...packageAliases,
      { find: /^@infra(\/.*)?$/, replacement: `${rootPosix}/infrastructure$1` },
    ],
  },
  test: {
    include: ['tests/**/*.test.ts'],
  },
});
