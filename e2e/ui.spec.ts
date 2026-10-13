import { test, expect, Page, BrowserContext } from '@playwright/test';

/**
 * UI flow against the live full-stack stack (see e2e/run.ps1):
 *
 *   signup (decoupled MSG91 OTP widget) → logout → login (OTP screen) → 3-attempt
 *   exhaustion (M6: local counter, no fabricated lock) → restart flow →
 *   successful login → logout
 *
 * The MSG91 client SDK script (https://verify.msg91.com/otp-provider.js) is
 * intercepted and mocked: code 000000 fails; any other 6-digit code returns
 * a verified JWT access token which Laravel redeems against the MSG91 stub.
 */

const PHONE = '9877001122'; // national digits → +919877001122 (dial code IN)

async function fillOtp(page: Page, code: string): Promise<void> {
  const boxes = page.locator('input[maxlength="1"]');
  await expect(boxes).toHaveCount(6);
  for (let i = 0; i < 6; i += 1) {
    await boxes.nth(i).fill(code[i]);
  }
}

async function hasSessionCookie(context: BrowserContext): Promise<boolean> {
  const cookies = await context.cookies();
  // matches `navdhya-session` and `__Host-navdhya-session` alike
  return cookies.some((c) => c.name.endsWith('navdhya-session') && c.value.length > 0);
}

async function logout(page: Page): Promise<void> {
  // Logout sits inside the header user dropdown (DashboardShell): open
  // the trigger first — the shell renders theme/bell/fullscreen/user
  // buttons, the user menu is last — then click the item.
  await page.locator('header').getByRole('button').last().click();
  await page.getByText('Logout', { exact: false }).first().click();
  await expect
    .poll(async () => hasSessionCookie(page.context()), { timeout: 20_000 })
    .toBe(false);
}

test('signup → login → OTP 3-attempt exhaustion → login again → logout', async ({ page, context }) => {
  // Intercept MSG91 widget script and install mock globals
  await page.route('**/otp-provider.js', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/javascript',
      body: `
        window.initSendOTP = function(config) {
          window.sendOtp = function(id, success) {
            success({ type: 'success', message: 'sent' });
          };
          window.retryOtp = function(channel, success) {
            success({ type: 'success', message: 'resent' });
          };
          window.verifyOtp = function(code, success, failure) {
            if (code === '000000') {
              failure({ type: 'error', message: 'invalid_code' });
            } else {
              success({ type: 'success', message: 'msg91-mock-jwt-token' });
            }
          };
          window.getWidgetData = function() { return {}; };
          window.isCaptchaVerified = function() { return true; };
        };
      `,
    });
  });

  // ---------------------------------------------------------- signup ----
  await page.goto('/signup');
  await page.getByPlaceholder('Phone number').fill(PHONE);
  await page.getByRole('button', { name: /create account/i }).click();

  await expect(page.getByText('Confirm Code')).toBeVisible({ timeout: 30_000 });
  await fillOtp(page, '123456');
  await page.getByRole('button', { name: /^verify$/i }).click();

  await page.waitForURL('**/user/overview', { timeout: 30_000 });
  await expect.poll(async () => hasSessionCookie(context)).toBe(true);

  // ---------------------------------------------------------- logout ----
  await logout(page);

  // -------------------------------------------------- login + exhaustion -
  // DashboardShell's logout routes to /login itself; an explicit goto
  // racing that client-side navigation aborts both. Tolerate the
  // race: navigate ourselves, then wait for the URL either way.
  await page.goto('/login').catch(() => {});
  await page.waitForURL('**/login', { timeout: 30_000 });

  const loginPhone = page.locator('input[placeholder="Phone number"]:visible');
  await expect(loginPhone).toHaveCount(1, { timeout: 15_000 });
  await loginPhone.fill(PHONE);
  await page.getByRole('button', { name: /^continue$/i }).click();
  await expect(page.getByText('Confirm Code')).toBeVisible({ timeout: 30_000 });

  // 3 wrong attempts (code 000000) → local counter exhausts the UX challenge (M6).
  await fillOtp(page, '000000');
  await page.getByRole('button', { name: /^verify$/i }).click();
  await expect(page.getByText(/2 attempts remaining/)).toBeVisible();

  await page.getByRole('button', { name: /^verify$/i }).click();
  await expect(page.getByText(/1 attempts remaining/)).toBeVisible();

  await page.getByRole('button', { name: /^verify$/i }).click();
  await expect(page.getByText('Too many failed attempts. Please request a new code.')).toBeVisible();

  // Exhausted: further submits stay blocked locally — and crucially there
  // is NO fabricated time-based lock.
  await page.getByRole('button', { name: /^verify$/i }).click();
  await expect(page.getByText('Too many failed attempts. Please request a new code.')).toBeVisible();
  await expect(page.getByText(/Try again in/)).toHaveCount(0);

  // Restart the flow: Back → Continue issues a fresh login challenge.
  await page.getByRole('button', { name: /^back$/i }).click();
  await page.getByPlaceholder('Phone number').fill(PHONE);
  await page.getByRole('button', { name: /^continue$/i }).click();
  await expect(page.getByText('Confirm Code')).toBeVisible({ timeout: 30_000 });

  await fillOtp(page, '123456');
  await page.getByRole('button', { name: /^verify$/i }).click();
  await page.waitForURL('**/user/overview', { timeout: 30_000 });
  await expect.poll(async () => hasSessionCookie(context)).toBe(true);

  // ------------------------------------------------ final logout --------
  await logout(page);
});
