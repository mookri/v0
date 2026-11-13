import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config covering all 4 Next.js BFF applications plus the full
 * end-to-end & control regression matrix:
 * - ui.spec.ts (auth / OTP lifecycle)
 * - console.spec.ts (role console smoke)
 * - booking-full-lifecycle.spec.ts (devotee -> pandit -> settlement lifecycle)
 * - catalog-discovery-lifecycle.spec.ts (re-enabled from orphan state)
 * - devotee-spiritual-lifecycle.spec.ts (re-enabled from orphan state)
 * - portfolio-control-matrix.spec.ts (16-scenario multi-app control & liveness matrix)
 */
export default defineConfig({
  testDir: '.',
  testMatch: /(ui|console|booking-full-lifecycle|catalog-discovery-lifecycle|devotee-spiritual-lifecycle|portfolio-control-matrix|jyotish-.*)\.spec\.ts/,
  timeout: 180_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list']],
  outputDir: './test-results',
  use: {
    baseURL: process.env.E2E_BFF ?? 'http://127.0.0.1:3000',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    actionTimeout: 15_000,
  },
  projects: [
    {
      name: 'chromium-web',
      use: { ...devices['Desktop Chrome'], baseURL: process.env.E2E_WEB_URL ?? process.env.E2E_BFF ?? 'http://127.0.0.1:3000' },
    },
    {
      name: 'chromium-customer',
      use: { ...devices['Desktop Chrome'], baseURL: process.env.E2E_CUSTOMER_URL ?? 'http://127.0.0.1:3001' },
    },
    {
      name: 'chromium-partner',
      use: { ...devices['Desktop Chrome'], baseURL: process.env.E2E_PARTNER_URL ?? 'http://127.0.0.1:3002' },
    },
    {
      name: 'chromium-dashboard',
      use: { ...devices['Desktop Chrome'], baseURL: process.env.E2E_DASHBOARD_URL ?? 'http://127.0.0.1:3003' },
    },
  ],
});
