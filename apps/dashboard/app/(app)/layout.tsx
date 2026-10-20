import { RequireAuth } from '@/components/RequireAuth';
import { Layout } from '@/components/Layout';

/** Authenticated shell: every page under this layout requires a session
 * (SPA PrivateRoute port) and renders inside the sidebar chrome. */
export default function AuthedLayout({ children }: { children: React.ReactNode }) {
  return (
    <RequireAuth>
      <Layout>{children}</Layout>
    </RequireAuth>
  );
}
