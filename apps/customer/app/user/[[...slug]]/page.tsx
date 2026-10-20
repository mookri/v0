import { redirect } from 'next/navigation';

/**
 * `/user` (and descendants) — production alias for the customer home dashboard.
 *
 * In production the web app redirects `/user/*` to this origin's `/user/*`
 * (see the web app's proxy). The customer app's canonical home is `/`, so
 * this route forwards there, preserving the query string for deep links.
 */
export default async function UserConsoleAlias({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const params = await searchParams;
  const qs = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (Array.isArray(value)) {
      for (const v of value) qs.append(key, v);
    } else if (typeof value === 'string') {
      qs.set(key, value);
    }
  }
  const target = qs.toString() ? `/?${qs.toString()}` : '/';
  redirect(target);
}
