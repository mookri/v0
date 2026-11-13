import { isSecureContext } from './federation';

/**
 * Build a strictly host-scoped HttpOnly session cookie (`__Host-` prefixed in
 * secure production, never emitting a wildcard `Domain=` attribute).
 */
export function buildHostSessionCookie(
  name: string,
  value: string,
  maxAgeSeconds = 900,
): string {
  const secure = isSecureContext() ? '; Secure' : '';
  const cookieName = isSecureContext() && !name.startsWith('__Host-') ? `__Host-${name}` : name;
  return `${cookieName}=${encodeURIComponent(value)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${maxAgeSeconds}${secure}`;
}

export function clearHostSessionCookie(name: string): string {
  const secure = isSecureContext() ? '; Secure' : '';
  const cookieName = isSecureContext() && !name.startsWith('__Host-') ? `__Host-${name}` : name;
  return `${cookieName}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`;
}

export function hasWildcardDomainAttribute(setCookieHeader: string): boolean {
  return /(?:^|;\s*)Domain=/i.test(setCookieHeader);
}

