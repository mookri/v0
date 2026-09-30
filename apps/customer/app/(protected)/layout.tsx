import type React from 'react';
import { CustomerAppShell } from '../../presentation/shells/CustomerAppShell';
import { AppBar } from '../../presentation/app-bar/AppBar';
import { BottomNavigation } from '../../presentation/navigation/BottomNavigation';

export default function ProtectedLayout({ children }: { children: React.ReactNode }) {
  return (
    <CustomerAppShell
      mode="compact"
      appBar={<AppBar config={{ title: 'Navdhya' }} />}
      navigation={<BottomNavigation />}
    >
      {children}
    </CustomerAppShell>
  );
}
