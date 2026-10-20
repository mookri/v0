import { redirect } from 'next/navigation';

/**
 * `/user` (and descendants) — production alias for the customer app.
 *
 * In production the web app redirects `/user/*` to this origin's `/user/*`
 * (see the web app's proxy). Map the known customer sections onto their
 * canonical routes so preserved deep links land on the matching page;
 * unknown descendants fall back to the dashboard rather than a 404.
 */
const KNOWN_ROOTS = new Set([
  'book',
  'bookings',
  'discover',
  'notifications',
  'pandits',
  'profile',
  'pujas',
  'saved',
  'settings',
  'support',
  'temples',
  'verifications',
]);

export default async function UserConsoleAlias({
  params,
  searchParams,
}: {
  params: Promise<{ slug?: string[] }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const [{ slug }, paramsQuery] = await Promise.all([params, searchParams]);
  const segments = slug ?? [];

  const qs = new URLSearchParams();
  for (const [key, value] of Object.entries(paramsQuery)) {
    if (Array.isArray(value)) {
      for (const v of value) qs.append(key, v);
    } else if (typeof value === 'string') {
      qs.set(key, value);
    }
  }

  const path =
    segments.length > 0 && KNOWN_ROOTS.has(segments[0])
      ? `/${segments.map((segment) => encodeURIComponent(segment)).join('/')}`
      : '/';

  redirect(qs.toString() ? `${path}?${qs.toString()}` : path);
}
