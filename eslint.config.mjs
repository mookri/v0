export default [
  {
    files: ['packages/**/*.{ts,tsx}', 'apps/**/*.{ts,tsx}', 'infrastructure/**/*.{ts,tsx}', 'tests/**/*.{ts,tsx}', 'workers/**/*.{ts,tsx}', 'tooling/**/*.{js,ts}'],
    rules: {
      'no-restricted-imports': [
        'error',
        {
          patterns: [
            { group: ['..', '../*'], message: 'Parent imports forbidden — use @ aliases (@/ for local, @navdhya/* for packages, @infra/* for infrastructure).' },
          ],
        },
      ],
    },
  },
];
