import next from 'eslint-config-next/core-web-vitals';

// Repo-wide import boundary. This rule existed before but never ran: the old
// config had no linter installed and `next lint` was removed in Next 16.
// Parent-directory imports are forbidden so every edge is explicit and
// reviewable (@/ for local, @navdhya/* for packages, @infra/* for infra).
const importBoundary = {
  files: ['**/*.{js,jsx,mjs,cjs,ts,tsx,mts,cts}'],
  rules: {
    'no-restricted-imports': [
      'error',
      {
        patterns: [
          {
            group: ['..', '../*'],
            message:
              'Parent imports forbidden — use @ aliases (@/ for local, @navdhya/* for packages, @infra/* for infrastructure).',
          },
        ],
      },
    ],
  },
};

// Migration debt, measured 2026-10-20 across apps/* (464 errors → 0):
//   react/no-unescaped-entities        246   cosmetic (apostrophes in JSX text)
//   react-hooks/error-boundaries        94   React Compiler rule added in hooks v7
//   @next/next/inline-script-id          75   JSON-LD <Script> blocks need ids
//   @next/next/no-html-link-for-pages    30   <a> → <Link> migration
//   react-hooks/set-state-in-effect      12   React Compiler rule added in hooks v7
//   react-hooks/immutability             4
//   react-hooks/refs / purity / globals  3
// These are downgraded to warnings, not disabled: the linter still reports them
// on every run and CI prints the totals. They are product migrations, not
// architecture rules — the architecture gates live in tests/architecture and
// the import boundary below stays a hard error. Removing an entry from this
// list is the ratchet step (docs/CI-CD-PIPELINE-PLAN.md §9).
const migrationDebt = {
  files: ['apps/**/*.{js,jsx,mjs,cjs,ts,tsx}'],
  rules: {
    'react/no-unescaped-entities': 'warn',
    'react-hooks/error-boundaries': 'warn',
    'react-hooks/set-state-in-effect': 'warn',
    'react-hooks/immutability': 'warn',
    'react-hooks/purity': 'warn',
    'react-hooks/refs': 'warn',
    'react-hooks/globals': 'warn',
    '@next/next/inline-script-id': 'warn',
    '@next/next/no-html-link-for-pages': 'warn',
  },
};

export default [
  {
    ignores: [
      '**/node_modules/**',
      '**/.next/**',
      '**/.turbo/**',
      '**/dist/**',
      '**/coverage/**',
      '**/graphify-out/**',
      // Nested Laravel repository (own .git, see docs/CI-CD-PIPELINE-PLAN.md F1).
      'platform-navdhya/**',
      // Scratch/CI output.
      'tmp/**',
      // Legacy prototype area excluded from the test graph already.
      'apps/web/websec/**',
    ],
  },
  ...next,
  importBoundary,
  migrationDebt,
];
