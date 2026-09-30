// components/dlo-x/shells/AdminShell.tsx
import { ReactNode } from 'react';
import { DashboardShell } from './DashboardShell';

interface AdminShellProps {
  children: ReactNode;
}

export function AdminShell({ children }: AdminShellProps) {
  return <DashboardShell>{children}</DashboardShell>;
}
