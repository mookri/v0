import type React from 'react';
import { CustomerPage } from './CustomerPage';

export function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <CustomerPage>
      <div data-layout="dashboard">{children}</div>
    </CustomerPage>
  );
}

export function ListLayout({ children }: { children: React.ReactNode }) {
  return (
    <CustomerPage>
      <div data-layout="list">{children}</div>
    </CustomerPage>
  );
}

export function DetailLayout({ children }: { children: React.ReactNode }) {
  return (
    <CustomerPage>
      <div data-layout="detail">{children}</div>
    </CustomerPage>
  );
}
