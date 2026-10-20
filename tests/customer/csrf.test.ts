import { test, expect } from 'vitest';
import { issueCsrfToken } from '@navdhya/security';
import { validateCsrf } from '@apps/customer/server/csrf';

/**
 * S44: the customer app's double-submit-cookie gate. The BFF routes don't
 * need it (Laravel mints and verifies CSRF itself per §10), but customer
 * app API routes that mutate state sit behind the session cookie and must
 * reject cross-site forgeries the same way — helper-level coverage here
 * so the contract is pinned before any route adopts it.
 */
function request(cookieToken?: string, headerToken?: string): Request {
  const headers: Record<string, string> = {};
  if (cookieToken !== undefined) headers['cookie'] = `navdhya-csrf=${cookieToken}`;
  if (headerToken !== undefined) headers['x-csrf-token'] = headerToken;
  return new Request('http://localhost/api/test', { headers });
}

test('matching cookie + header passes the double-submit check', () => {
  const { token } = issueCsrfToken();
  expect(validateCsrf(request(token, token))).toEqual({ ok: true });
});

test('missing cookie → ok:false missing-cookie', () => {
  const { token } = issueCsrfToken();
  expect(validateCsrf(request(undefined, token))).toEqual({
    ok: false,
    error: 'missing-cookie',
  });
});

test('missing header → ok:false missing-header', () => {
  const { token } = issueCsrfToken();
  expect(validateCsrf(request(token, undefined))).toEqual({
    ok: false,
    error: 'missing-header',
  });
});

test('mismatched values → ok:false mismatch (the forgery case)', () => {
  const { token } = issueCsrfToken();
  const other = issueCsrfToken();
  expect(validateCsrf(request(token, other.token))).toEqual({
    ok: false,
    error: 'mismatch',
  });
});

test('garbage cookie + echoed header → ok:false (signature check)', () => {
  const forged = 'not-a-real-token';
  const result = validateCsrf(request(forged, forged));
  expect(result.ok).toBe(false);
  expect(result.error).toBeTruthy();
});
