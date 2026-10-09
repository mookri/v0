import { test, expect, Page, BrowserContext } from '@playwright/test';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';

/**
 * UI flow against the live full-stack stack (see e2e/run.ps1):
 *
 *   signup (real OTP) → logout → login (OTP screen) → 3-attempt
 *   exhaustion (M6: local counter, no fabricated lock) → restart flow →
 *   successful login → logout
 *
 * OTP codes come from the Laravel drill log (OTP_LOG_CODES=true —
 * config/customers.php log_otp_codes, off by default, enabled only by
 * the E2E orchestrator). Codes are unguessable challenge secrets; this
 * reads the server-side drill log, it does not weaken the challenge.
 */

const LOG_DIR =
  process.env.E2E_LARAVEL_LOG_DIR ??
  join(process.cwd(), '..', 'platform-navdhya', 'storage', 'logs');

const PHONE = '9877001122'; // national digits → +919877001122 (dial code IN)

function latestOtpCode(): string {
  const files = readdirSync(LOG_DIR)
    .filter((f) => /^laravel.*\.log$/.test(f))
    .map((f) => ({ f, m: statSync(join(LOG_DIR, f)).mtimeMs }))
    .sort((a, b) => b.m - a.m);
  for (const { f } of files) {
    const text = readFileSync(join(LOG_DIR, f), 'utf8');
    const re = /OTP challenge issued \(drill-only code log\)[\s\S]{0,500}?"code":"(\d{4,8})"/g;
    let m: RegExpExecArray | null;
    let last = '';
    while ((m = re.exec(text)) !== null) last = m[1];
    if (last) return last;
  }
  throw new Error(`no drill OTP code found in ${LOG_DIR} (OTP_LOG_CODES on?)`);
}

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
  // ---------------------------------------------------------- signup ----
  await page.goto('/signup');
  await page.getByPlaceholder('Phone number').fill(PHONE);
  await page.getByRole('button', { name: /create account/i }).click();

  await expect(page.getByText('Confirm Code')).toBeVisible({ timeout: 30_000 });
  await fillOtp(page, latestOtpCode());
  await page.getByRole('button', { name: /^verify$/i }).click();

  await page.waitForURL('**/user/overview', { timeout: 30_000 });
  await expect.poll(async () => hasSessionCookie(context)).toBe(true);

  // ---------------------------------------------------------- logout ----
  await logout(page);

  // -------------------------------------------------- login + exhaustion -
  // DashboardShell's logout routes to /login itself; an explicit goto
  // racing that client-side navigation aborts both (Playwright
  // "navigation interrupted by another navigation" — observed in the
  // 2026-10-09 post-fix run, page already at /login). Tolerate the
  // race: navigate ourselves, then wait for the URL either way.
  await page.goto('/login').catch(() => {});
  await page.waitForURL('**/login', { timeout: 30_000 });
  await page.getByPlaceholder('Phone number').fill(PHONE);
  await page.getByRole('button', { name: /^continue$/i }).click();
  await expect(page.getByText('Confirm Code')).toBeVisible({ timeout: 30_000 });

  // 3 wrong attempts → local counter exhausts the UX challenge (M6).
  await fillOtp(page, '000000');
  await page.getByRole('button', { name: /^verify$/i }).click();
  await expect(page.getByText(/2 attempts remaining/)).toBeVisible();

  await page.getByRole('button', { name: /^verify$/i }).click();
  await expect(page.getByText(/1 attempts remaining/)).toBeVisible();

  await page.getByRole('button', { name: /^verify$/i }).click();
  await expect(page.getByText('Too many failed attempts. Please request a new code.')).toBeVisible();

  // Exhausted: further submits stay blocked locally — and crucially there
  // is NO fabricated time-based lock (M6 fix: the old 15-min lock is gone).
  await page.getByRole('button', { name: /^verify$/i }).click();
  await expect(page.getByText('Too many failed attempts. Please request a new code.')).toBeVisible();
  await expect(page.getByText(/Try again in/)).toHaveCount(0);

  // Restart the flow (no waiting on the 60s resend timer): Back → Continue
  // issues a fresh login challenge.
  await page.getByRole('button', { name: /^back$/i }).click();
  await page.getByPlaceholder('Phone number').fill(PHONE);
  await page.getByRole('button', { name: /^continue$/i }).click();
  await expect(page.getByText('Confirm Code')).toBeVisible({ timeout: 30_000 });

  await fillOtp(page, latestOtpCode());
  await page.getByRole('button', { name: /^verify$/i }).click();
  await page.waitForURL('**/user/overview', { timeout: 30_000 });
  await expect.poll(async () => hasSessionCookie(context)).toBe(true);

  // ------------------------------------------------ final logout --------
  await logout(page);
});
