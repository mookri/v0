import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config for the UI flow (signup → OTP 3-attempt exhaustion →
 * login → logout) against an already-running stack. e2e/run.ps1 boots
 * Laravel (:8000) and the BFF (:3000) and restarts the BFF before this
 * phase so the in-process auth rate limiters start fresh.
 *
 * No webServer block: servers are externally orchestrated so the HTTP
 * scenario phase and the UI phase can share the same MySQL state while
 * getting independent rate-limiter budgets.
 */
export default defineConfig({
  testDir: '.',
  // Flows run: the auth/OTP lifecycle (ui.spec.ts), the role-console smoke (console.spec.ts),
  // and the comprehensive end-to-end booking lifecycle & quality suite (booking-full-lifecycle.spec.ts).
  testMatch: /(ui|console|booking-full-lifecycle)\.spec\.ts/,
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
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
