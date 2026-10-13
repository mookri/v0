import { defineConfig } from 'vitest/config';
import * as path from 'node:path';

// Workspace links (pnpm install) are not required: aliases resolve the
// monorepo sources directly, mirroring the root vitest config's behavior.
const root = path.resolve(__dirname, '../../..');

export default defineConfig({
  resolve: {
    alias: [
      { find: /^@navdhya\/([^/]+)\/(.+)$/, replacement: `${root}/packages/$1/$2` },
      { find: /^@navdhya\/([^/]+)$/, replacement: `${root}/packages/$1/src/index.ts` },
      { find: /^@infra\/(.+)$/, replacement: `${root}/infrastructure/$1` },
    ],
  },
});
