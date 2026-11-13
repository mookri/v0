import { describe, expect, it, vi, afterEach } from 'vitest';
import {
  buildHostSessionCookie,
  clearHostSessionCookie,
  hasWildcardDomainAttribute,
  assertAudienceMatches,
  createBackchannelLogoutStore,
} from '@navdhya/auth-client';
import { createBackendClient, StepUpRequiredError, BackendApiError } from '@navdhya/api-client';

describe('Shared @navdhya/auth-client & @navdhya/api-client contracts (E-AP-01)', () => {
  const origNodeEnv = process.env.NODE_ENV;
  const origSecure = process.env.SESSION_COOKIE_SECURE;

  afterEach(() => {
    vi.restoreAllMocks();
    process.env.NODE_ENV = origNodeEnv;
    if (origSecure === undefined) delete process.env.SESSION_COOKIE_SECURE;
    else process.env.SESSION_COOKIE_SECURE = origSecure;
  });

  it('emits strictly host-only cookies (never Domain=.navdhya.com)', () => {
    process.env.SESSION_COOKIE_SECURE = '1';
    const set = buildHostSessionCookie('__session', 'tok-1', 900);
    const clear = clearHostSessionCookie('__session');
    expect(set).toContain('__Host-__session=');
    expect(set).toContain('HttpOnly');
    expect(set).toContain('SameSite=Lax');
    expect(set).toContain('Secure');
    expect(hasWildcardDomainAttribute(set)).toBe(false);
    expect(hasWildcardDomainAttribute(clear)).toBe(false);
  });

  it('enforces exact client audience matching', () => {
    expect(assertAudienceMatches('client-dashboard', 'client-dashboard')).toBe(true);
    expect(assertAudienceMatches(['client-a', 'client-dashboard'], 'client-dashboard')).toBe(true);
    expect(assertAudienceMatches('client-customer', 'client-dashboard')).toBe(false);
    expect(assertAudienceMatches(undefined, 'client-dashboard')).toBe(false);
  });

  it('tracks terminated SIDs and rejects replayed logout JTIs', () => {
    const store = createBackchannelLogoutStore();
    expect(store.rememberLogoutJti('jti-1')).toBe(true);
    expect(store.rememberLogoutJti('jti-1')).toBe(false);
    store.markSidTerminated('sid-1');
    expect(store.isSidTerminated('sid-1')).toBe(true);
    expect(store.payloadSidTerminated({ sid: 'sid-1' })).toBe(true);
  });

  it('throws StepUpRequiredError on 403 step_up_required and maps conflict codes', async () => {
    const client = createBackendClient({
      baseUrl: () => 'https://api.navdhya.test',
      tenantId: () => 'tenant-1',
    });

    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(
        JSON.stringify({
          error: { code: 'step_up_required', request_id: 'rid-step' },
          required_acr: '2',
          challenge_url: '/oauth/step-up',
          detail: 'MFA step-up required.',
        }),
        { status: 403, headers: { 'content-type': 'application/json' } },
      ),
    );

    await expect(client.post('tok', '/payouts/1/approve', {})).rejects.toBeInstanceOf(
      StepUpRequiredError,
    );

    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(
        JSON.stringify({
          code: 'invalid_state_transition',
          detail: 'Cannot assign completed booking',
          request_id: 'rid-conflict',
        }),
        { status: 409, headers: { 'content-type': 'application/problem+json' } },
      ),
    );

    try {
      await client.post('tok', '/v2/booking/reservations/1/assign', {}, { apiVersion: 'v2' });
      expect.unreachable();
    } catch (e) {
      expect(e).toBeInstanceOf(BackendApiError);
      expect((e as BackendApiError).code).toBe('CONFLICT');
    }
  });
});

