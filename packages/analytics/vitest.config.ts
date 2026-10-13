import { defineConfig } from 'vitest/config';
import * as path from 'node:path';

// Package-level vitest runs WITHOUT the root config's alias plugin, so
// intra-package @navdhya/analytics/... aliases (imposed by the import-alias
// architecture test: no parent-directory imports) resolve here directly from
// monorepo sources — no workspace install required.
const root = path.resolve(__dirname, '../..');

export default defineConfig({
  resolve: {
    alias: [
      { find: /^@navdhya\/([^/]+)\/(.+)$/, replacement: `${root}/packages/$1/$2` },
      { find: /^@navdhya\/([^/]+)$/, replacement: `${root}/packages/$1/src/index.ts` },
      { find: /^@infra\/(.+)$/, replacement: `${root}/infrastructure/$1` },
    ],
  },
});
