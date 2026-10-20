// components/dlo-x/shells/VendorShell.tsx
import { ReactNode } from 'react';
import { DashboardShell } from './DashboardShell';

interface VendorShellProps {
  children: ReactNode;
}

export function VendorShell({ children }: VendorShellProps) {
  return <DashboardShell>{children}</DashboardShell>;
}
