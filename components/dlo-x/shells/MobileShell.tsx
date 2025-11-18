// components/dlo-x/shells/MobileShell.tsx
import { ReactNode } from 'react';

interface MobileShellProps {
  children: ReactNode;
}

export function MobileShell({ children }: MobileShellProps) {
  return (
    <div className="min-h-screen bg-background lg:hidden">
      {/* Mobile-optimized layout */}
      <div className="pb-16"> {/* Padding for bottom menu */}
        {children}
      </div>
    </div>
  );
}