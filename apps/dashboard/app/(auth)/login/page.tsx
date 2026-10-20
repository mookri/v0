import { Suspense } from 'react';
import { redirect } from 'next/navigation';
import { isFederated, safeNext } from '@/server/federation';
import LoginClient from './client';

/**
 * /login — AUTH_MODE cutover (ADR-0020): federated deployments own no
 * credential form — the browser is handed to the auth subdomain's
 * unified login and comes back through /api/auth/callback. Direct
 * deployments keep today's email/password form.
 */
export default async function LoginPage({ searchParams }: { searchParams: Promise<{ redirect?: string }> }) {
  if (isFederated()) {
    const { redirect: target } = await searchParams;
    const next = safeNext(target) ?? '/';
    redirect(`/api/auth/authorize?redirect=${encodeURIComponent(next)}`);
  }
  // Suspense: LoginClient reads useSearchParams, which requires a
  // boundary during static prerender.
  return (
    <Suspense fallback={null}>
      <LoginClient />
    </Suspense>
  );
}
