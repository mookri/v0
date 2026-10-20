import type { Config } from 'jest';
import nextJest from 'next/jest.js';

const createJestConfig = nextJest({
  dir: './',
});

const config: Config = {
  setupFilesAfterEnv: ['<rootDir>/jest.setup.ts'],
  // Jest's default testMatch is `**/__tests__/**/*.[jt]s?(x)`, which treats every
  // file under a __tests__ directory as a suite. This app keeps dev preview
  // pages (components/__tests__/preloader/*/page.tsx) next to its tests, so the
  // default reports "Your test suite must contain at least one test" and fails
  // the whole run. Match on the name, not the directory.
  testMatch: [
    '<rootDir>/**/*.test.ts',
    '<rootDir>/**/*.test.tsx',
    '<rootDir>/**/*.spec.ts',
    '<rootDir>/**/*.spec.tsx',
  ],
  testEnvironment: 'jest-environment-jsdom',
  moduleNameMapper: {
    '^@/components/(.*)$': '<rootDir>/components/$1',
    '^@/lib/(.*)$': '<rootDir>/lib/$1',
  },
  modulePathIgnorePatterns: ['<rootDir>/.next/', '<rootDir>/websec/', '<rootDir>/drafts/'],
  testPathIgnorePatterns: ['<rootDir>/.next/', '<rootDir>/node_modules/', '<rootDir>/websec/', '<rootDir>/drafts/'],
  watchPathIgnorePatterns: ['<rootDir>/.next/', '<rootDir>/websec/', '<rootDir>/drafts/'],
};

export default createJestConfig(config);
