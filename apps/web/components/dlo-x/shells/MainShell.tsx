// components/dlo-x/shells/MainShell.tsx
import { ReactNode } from 'react';
import { SiteHeader } from '@/components/site-header';
import { SiteFooter } from '@/components/site-footer';
import BottomMenu from '@/components/bottom-menu';

interface MainShellProps {
  children: ReactNode;
}

export function MainShell({ children }: MainShellProps) {
  return (
    <div>
      <SiteHeader />
      {children}
      <SiteFooter />
      <BottomMenu />
    </div>
  );
}