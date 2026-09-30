import type { Config } from 'jest';
import nextJest from 'next/jest.js';

const createJestConfig = nextJest({
  dir: './',
});

const config: Config = {
  setupFilesAfterEnv: ['<rootDir>/jest.setup.ts'],
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
