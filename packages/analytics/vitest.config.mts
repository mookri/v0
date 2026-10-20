import { defineConfig } from 'vitest/config';
import * as path from 'node:path';
import { fileURLToPath } from 'node:url';

// Package-level vitest runs WITHOUT the root config's alias plugin, so
// intra-package @navdhya/analytics/... aliases (imposed by the import-alias
// architecture test: no parent-directory imports) resolve here directly from
// monorepo sources — no workspace install required.
// .mts (not .ts): a CJS-context package.json makes vite bundle .ts configs
// through vitest's CJS entry, which cannot require() the ESM-only std-env.
const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');

export default defineConfig({
  resolve: {
    alias: [
      { find: /^@navdhya\/([^/]+)\/(.+)$/, replacement: `${root}/packages/$1/$2` },
      { find: /^@navdhya\/([^/]+)$/, replacement: `${root}/packages/$1/src/index.ts` },
      { find: /^@infra\/(.+)$/, replacement: `${root}/infrastructure/$1` },
    ],
  },
});
