import { validateCsrfToken, CSRF_CONSTANTS } from '@navdhya/security';

export function validateCsrf(request: Request): { ok: boolean; error?: string } {
  const cookieHeader = request.headers.get('cookie') ?? '';
  const cookieMatch = cookieHeader.match(new RegExp(`(?:^|;\\s*)${CSRF_CONSTANTS.COOKIE}=([^;]*)`));
  const cookie = cookieMatch ? cookieMatch[1] : undefined;
  const header = request.headers.get(CSRF_CONSTANTS.HEADER) ?? undefined;

  const result = validateCsrfToken(cookie, header);
  if (!result.ok) {
    return { ok: false, error: result.reason };
  }
  return { ok: true };
}