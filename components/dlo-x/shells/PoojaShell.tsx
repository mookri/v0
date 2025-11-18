// components/dlo-x/shells/PoojaShell.tsx
import { ReactNode } from 'react';

interface PoojaShellProps {
  children: ReactNode;
}

export function PoojaShell({ children }: PoojaShellProps) {
  return (
    <div className="min-h-screen bg-background">
      {/* Pooja-specific header or navigation could go here */}
        {children}
    </div>
  );
}