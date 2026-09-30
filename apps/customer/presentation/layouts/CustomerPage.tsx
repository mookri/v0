import type React from 'react';

export function CustomerPage({ children }: { children: React.ReactNode }) {
  return (
    <div data-shell="content-surface" style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}>
      {children}
    </div>
  );
}
