import { describe, expect, it } from 'vitest';
import { NextRequest } from 'next/server';
import { proxy } from '@/proxy';
import { sessionCookieName } from '@/server/session';

/**
 * W4 (docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md): apps/dashboard was the
 * only console surface with no edge guard at all. This test pins the new
 * fail-closed posture.
 */
function nreq(path: string, cookie?: string) {
  const headers: Record<string, string> = {};
  if (cookie) headers.cookie = cookie;
  return new NextRequest(`http://localhost${path}`, { headers });
}

describe('dashboard proxy guard (W4)', () => {
  const SESSION = `${sessionCookieName()}=test-token`;

  it('redirects an anonymous visitor on a protected page to /login with next', () => {
    const response = proxy(nreq('/bookings'));
    expect(response.status).toBe(302);
    expect(response.headers.get('location')).toBe('http://localhost/login?next=%2Fbookings');
  });

  it('admits a session-cookie holder', () => {
    const response = proxy(nreq('/bookings', SESSION));
    expect(response.status).toBe(200);
  });

  it('leaves public surfaces (login, callback, backchannel-logout) reachable', () => {
    expect(proxy(nreq('/login')).status).toBe(200);
    expect(proxy(nreq('/api/auth/callback')).status).toBe(200);
    expect(proxy(nreq('/api/auth/backchannel-logout')).status).toBe(200);
  });

  it('is segment-safe', () => {
    const response = proxy(nreq('/bookingsx'));
    expect(response.status).toBe(302);
  });
});
