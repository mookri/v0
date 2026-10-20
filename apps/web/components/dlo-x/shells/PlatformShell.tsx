// components/dlo-x/shells/PlatformShell.tsx
import { ReactNode } from 'react';
import { DashboardShell } from './DashboardShell';

interface PlatformShellProps {
  children: ReactNode;
}

export function PlatformShell({ children }: PlatformShellProps) {
  return <DashboardShell>{children}</DashboardShell>;
}
