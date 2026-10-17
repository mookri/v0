import { redirect } from 'next/navigation';
import { isFederated, safeNext } from '@/server/federation';
import LoginClient from './client';

export default async function LoginPage({ searchParams }: { searchParams: Promise<{ redirect?: string }> }) {
  // AUTH_MODE cutover (ADR-0020): federated deployments own no
  // credential form — the browser is handed to the auth subdomain's
  // unified login and comes back through /api/auth/callback.
  if (isFederated()) {
    const { redirect: target } = await searchParams;
    const next = safeNext(target) ?? '/';
    redirect(`/api/auth/authorize?redirect=${encodeURIComponent(next)}`);
  }
  return <LoginClient />;
}
