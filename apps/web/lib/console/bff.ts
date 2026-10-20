import { cookies } from 'next/headers';
import type { ConsoleEnvelope } from '@/lib/console/envelope';

/**
 * Server-side fetch of this app's own BFF route — the console panels' single
 * network entry point (W1a).
 *
 * Mirrors the established `AdminDashboardReal` convention: a **relative** URL
 * (never the public origin, so a dev/E2E server can never reach production)
 * plus the incoming request's cookies so the BFF can authenticate the caller's
 * session. Reading `cookies()` also opts the panel out of static prerender — a
 * per-actor view must never be cached across actors.
 *
 * Unlike the older helper this returns the envelope for *any* status instead
 * of throwing on non-2xx: a 401/403/404 from the BFF is a meaningful, renderable
 * answer, not an exception. Only a non-JSON body throws.
 */
export async function bffJSON<T>(path: string): Promise<ConsoleEnvelope<T>> {
  const store = await cookies();
  const cookieHeader = store
    .getAll()
    .map((cookie) => `${cookie.name}=${cookie.value}`)
    .join('; ');

  const res = await fetch(path, {
    headers: {
      'Content-Type': 'application/json',
      ...(cookieHeader ? { cookie: cookieHeader } : {}),
    },
    cache: 'no-store',
  });

  const body = (await res.json().catch(() => null)) as ConsoleEnvelope<T> | null;

  if (body === null) {
    throw new Error(`BFF ${path} responded ${res.status} without a JSON body`);
  }

  return body;
}
