'use client';

import { useAuth } from '@/hooks/useAuth';
import { redirect } from 'next/navigation';

/** SPA PrivateRoute port: hold on boot validation, bounce to /login
 * when the session does not resolve (federated cookie or direct token). */
export function RequireAuth({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center">Loading...</div>;
  }

  if (!isAuthenticated) {
    redirect('/login');
  }

  return <>{children}</>;
}
