import { defineConfig } from 'vitest/config';
import * as path from 'node:path';

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
