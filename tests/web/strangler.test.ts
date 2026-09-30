import { test, expect } from 'vitest';
import { stranglerTarget, stranglerFlagsFromEnv } from '@apps/web/lib/strangler/route-map';

const OFF = { bookLive: false, userLive: false, settingsLive: false, customerOrigin: 'https://app.navdhya.com' };
const ON = { bookLive: true, userLive: true, settingsLive: true, customerOrigin: 'https://app.navdhya.com' };

test('flag off: everything serves local (no observable change)', () => {
  for (const p of ['/', '/pujas/lakshmi', '/book', '/user/bookings', '/settings', '/admin/x', '/pandit/y']) {
    expect(stranglerTarget(p, OFF)).toEqual({ action: 'serve-local' });
  }
});

test('flag on: customer classes redirect, everything else stays', () => {
  expect(stranglerTarget('/book', ON)).toEqual({ action: 'redirect', target: 'https://app.navdhya.com/book' });
  expect(stranglerTarget('/user/bookings/b1', ON)).toEqual({
    action: 'redirect',
    target: 'https://app.navdhya.com/user/bookings/b1',
  });
  expect(stranglerTarget('/settings', ON)).toEqual({ action: 'redirect', target: 'https://app.navdhya.com/settings' });
  expect(stranglerTarget('/', ON)).toEqual({ action: 'serve-local' });
  expect(stranglerTarget('/pujas/x', ON)).toEqual({ action: 'serve-local' });
  expect(stranglerTarget('/admin/x', ON)).toEqual({ action: 'serve-local' });
  expect(stranglerTarget('/pandit/y', ON)).toEqual({ action: 'serve-local' });
  // Prefix trap: /booking-history is NOT /book/**
  expect(stranglerTarget('/booking-history', ON)).toEqual({ action: 'serve-local' });
});

test('classes flip independently (runbook order)', () => {
  const bookOnly = { ...OFF, bookLive: true };
  expect(stranglerTarget('/book', bookOnly).action).toBe('redirect');
  expect(stranglerTarget('/user', bookOnly)).toEqual({ action: 'serve-local' });
  expect(stranglerTarget('/settings', bookOnly)).toEqual({ action: 'serve-local' });
});

test('flags derive from env with safe defaults', () => {
  expect(stranglerFlagsFromEnv({})).toEqual({ ...OFF });
  expect(stranglerFlagsFromEnv({ STRANGLER_CUSTOMER_LIVE: '1' })).toEqual({ ...ON });
  expect(
    stranglerFlagsFromEnv({ STRANGLER_CUSTOMER_LIVE: '1', STRANGLER_CUSTOMER_USER: '0', NEXT_PUBLIC_CUSTOMER_ORIGIN: 'https://staging.example' }),
  ).toEqual({ bookLive: true, userLive: false, settingsLive: true, customerOrigin: 'https://staging.example' });
});
