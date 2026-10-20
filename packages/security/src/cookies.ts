export type SessionAudience = 'web' | 'customer' | 'partner' | 'admin' | 'operations';

export const SESSION_COOKIES: Record<SessionAudience, string> = {
  web: '__Host-nv_web',
  customer: '__Host-nv_customer',
  partner: '__Host-nv_partner',
  admin: '__Host-nv_admin',
  operations: '__Host-nv_ops',
};

export function buildSessionCookie(audience: SessionAudience, value: string, maxAgeSeconds: number): string {
  return `${SESSION_COOKIES[audience]}=${value}; Path=/; Secure; HttpOnly; SameSite=Lax; Max-Age=${maxAgeSeconds}`;
}

const ALLOWED_RETURN = [/^\/$/, /^\/bookings(\/\w+)?$/, /^\/profile$/, /^\/pujas\/[\w-]+$/];

export function validateReturnPath(path: string | undefined): string | null {
  if (!path) return null;
  if (path.startsWith('http') || path.startsWith('//') || path.startsWith('javascript:') || path.startsWith('data:')) return null;
  if (!path.startsWith('/')) return null;
  return ALLOWED_RETURN.some((re) => re.test(path)) ? path : null;
}
