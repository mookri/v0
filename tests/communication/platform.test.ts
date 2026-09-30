import { test, expect } from 'vitest';
import { isChannelAllowed } from '../../packages/notifications/src/domain/notification-preference';
import { unreadCount } from '../../packages/notifications/src/application/notifications';
import { renderTemplate, dedupKey, classifyProviderError, backoffMs } from '../../packages/communication/src/domain/templates';
import { selectChannel, fallbackChannel, routeProvider } from '../../packages/communication/src/orchestration/dispatch';
import { FakeChannelProvider } from '../../packages/communication/src/adapters/fake-provider';

const NOW = '2026-09-30T00:00:00.000Z';

test('preferences: security bypasses opt-out, marketing needs consent', () => {
  const pref = { userId: 'u', category: 'marketing' as const, channels: { whatsapp: true }, marketingConsent: false };
  expect(isChannelAllowed(pref, 'marketing', 'whatsapp')).toBe(false);
  expect(isChannelAllowed(pref, 'security', 'whatsapp')).toBe(true);
  expect(isChannelAllowed({ ...pref, marketingConsent: true }, 'marketing', 'whatsapp')).toBe(true);
});

test('unread count derives from readAt timestamps', () => {
  const mk = (readAt?: string) => ({
    id: 'n', type: 't', recipient: { userId: 'u' }, audience: 'customer' as const,
    category: 'transactional' as const, criticality: 'high' as const,
    title: 't', body: 'b', createdAt: NOW, status: 'CREATED' as const, ...(readAt ? { readAt } : {}),
  });
  expect(unreadCount([mk(), mk(NOW), mk()])).toBe(2);
});

test('templates fail closed on missing/unknown variables and scripts', () => {
  const t = { id: 'booking.confirmed', version: 'v4', locale: 'en-IN', status: 'ACTIVE' as const, variables: { date: 'string' as const }, body: 'Confirmed for {{date}}.' };
  expect(renderTemplate(t, { date: 'Oct 5' })).toBe('Confirmed for Oct 5.');
  expect(() => renderTemplate(t, {})).toThrow('TEMPLATE_VARIABLE_MISSING');
  expect(() => renderTemplate(t, { date: 'x', extra: 'y' })).toThrow('TEMPLATE_VARIABLE_UNKNOWN');
  expect(() => renderTemplate({ ...t, body: '<script>x</script>' }, { date: 'x' })).toThrow('TEMPLATE_UNSAFE');
});

test('channel policy honors quiet hours; fallback is policy-driven', () => {
  expect(selectChannel({ preferred: ['whatsapp'], category: 'marketing', quietHoursActive: true })).toBeNull();
  expect(selectChannel({ preferred: ['whatsapp'], category: 'security', quietHoursActive: true })).toBe('whatsapp');
  expect(fallbackChannel('whatsapp')).toBe('sms');
  expect(fallbackChannel('sms')).toBe('email');
  expect(fallbackChannel('email')).toBeNull();
});

test('dedup keys are stable; retry classification splits failures', () => {
  expect(dedupKey({ rule: 'r', eventId: 'e', userId: 'u', channel: 'whatsapp' })).toBe('r:e:u:whatsapp');
  expect(classifyProviderError('TIMEOUT')).toBe('RETRYABLE');
  expect(classifyProviderError('INVALID')).toBe('PERMANENT');
  expect(backoffMs(0)).toBe(500);
});

test('fake provider routes by channel with scripted outcomes', async () => {
  const wa = new FakeChannelProvider('whatsapp', 'SUCCESS');
  const sms = new FakeChannelProvider('sms', 'TIMEOUT');
  expect(routeProvider([wa, sms], 'whatsapp')).toBe(wa);
  expect((await wa.send()).accepted).toBe(true);
  expect((await sms.send()).errorCode).toBe('TIMEOUT');
});
