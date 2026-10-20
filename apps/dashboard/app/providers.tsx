'use client';

import { AuthProvider } from '@/hooks/useAuth';

/** Client boundary for app-wide auth state (SPA AuthProvider port). */
export function Providers({ children }: { children: React.ReactNode }) {
  return <AuthProvider>{children}</AuthProvider>;
}
