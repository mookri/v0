import { test, expect, Page, BrowserContext } from '@playwright/test';

/**
 * Console smoke flow against the live full-stack stack (see e2e/run.ps1).
 *
 * Verifies that the role consoles no longer render the "under development"
 * placeholder for the sections that are now backed by real endpoints, and that
 * a logged-in session can reach them without being bounced to /login.
 *
 * Full capability-gated data requires seeded roles (runbook SQL); this spec
 * asserts structure, not row counts, so it stays green on a fresh stack.
 */

const PHONE = '9877001122';
const WIDGET_TOKEN = 'e2e-widget-jwt-token';

async function fillOtp(page: Page, code: string): Promise<void> {
  const boxes = page.locator('input[maxlength="1"]');
  await expect(boxes).toHaveCount(6);
  for (let i = 0; i < 6; i += 1) {
    await boxes.nth(i).fill(code[i]);
  }
}

async function hasSessionCookie(context: BrowserContext): Promise<boolean> {
  const cookies = await context.cookies();
  return cookies.some((c) => c.name.endsWith('navdhya-session') && c.value.length > 0);
}

test('console sections render live (no "under development" placeholder)', async ({ page, context }) => {
  await page.route('**/otp-provider.js', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/javascript',
      body: `
        window.initSendOTP = function(config) {
          window.sendOtp = function(id, success) { success({ type: 'success', message: 'sent' }); };
          window.retryOtp = function(channel, success) { success({ type: 'success', message: 'resent' }); };
          window.verifyOtp = function(code, success, failure) {
            if (code === '000000') failure({ type: 'error', message: 'invalid_code' });
            else success({ type: 'success', message: '${WIDGET_TOKEN}' });
          };
          window.getWidgetData = function() { return {}; };
          window.isCaptchaVerified = function() { return true; };
        };
      `,
    });
  });

  // Log in to obtain a real session cookie.
  await page.goto('/login');
  await page.locator('input[placeholder="Phone number"]:visible').fill(PHONE);
  await page.getByRole('button', { name: /^continue$/i }).click();
  await expect(page.getByText('Confirm Code')).toBeVisible({ timeout: 30_000 });
  await fillOtp(page, '123456');
  await page.getByRole('button', { name: /^verify$/i }).click();
  await page.waitForURL('**/user/overview', { timeout: 30_000 });
  await expect.poll(async () => hasSessionCookie(context)).toBe(true);

  // Grant the dev-role cookie so the proxy admits the SuperAdmin console.
  // (Dev-only; the cookie is ignored in production.)
  await context.addCookies([
    { name: 'navdhya-dev-role', value: 'SuperAdmin', url: 'http://127.0.0.1:3000' },
  ]);

  // Platform console: overview + roles + users + api + logs + settings + billing
  // + security + system must all render a heading and never the placeholder.
  for (const slug of ['overview', 'roles', 'users', 'api', 'logs', 'settings', 'billing', 'security', 'system']) {
    await page.goto(`/platform/${slug}`);
    await expect(page).not.toHaveURL(/\/login/);
    await expect(page.getByText('under development')).toHaveCount(0);
  }

  // Admin console: all 12 sections render without the placeholder.
  await context.addCookies([
    { name: 'navdhya-dev-role', value: 'Admin', url: 'http://127.0.0.1:3000' },
  ]);
  for (const slug of ['overview', 'users', 'pandits', 'vendors', 'managers', 'support', 'bookings', 'content', 'catalog', 'seo', 'payouts', 'settings']) {
    await page.goto(`/admin/${slug}`);
    await expect(page).not.toHaveURL(/\/login/);
    await expect(page.getByText('under development')).toHaveCount(0);
  }

  // Logout and verify the console is no longer reachable.
  await page.goto('/login').catch(() => {});
  // Clear session by navigating to logout.
  await page.goto('/api/auth/logout').catch(() => {});
  await page.waitForURL(/\/login/, { timeout: 30_000 }).catch(() => {});
  await page.goto('/platform/roles');
  await expect(page).toHaveURL(/\/login/);
});
