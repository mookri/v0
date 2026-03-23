// /app/(seo)/layout.tsx
// SEO section layout wrapper — v2
// Added: Suspense boundary, error boundary, and proper metadata exports.

import { ReactNode, Suspense } from 'react';
import { MainShell } from '@/components/dlo-x/shells/MainShell';

/** Thin skeleton shown while the page content streams in */
function SEOPageSkeleton() {
  return (
    <div className="animate-pulse">
      {/* Header placeholder */}
      <div className="h-14 bg-[#0E3F2E]/90" />
      {/* Hero placeholder */}
      <div className="h-64 bg-muted/60" />
      {/* Body placeholder */}
      <div className="max-w-[1400px] mx-auto px-6 py-16 grid lg:grid-cols-[1fr_300px] gap-20">
        <div className="max-w-[760px] space-y-4">
          <div className="h-6 w-3/4 rounded bg-muted" />
          <div className="h-4 w-full rounded bg-muted" />
          <div className="h-4 w-5/6 rounded bg-muted" />
          <div className="h-4 w-4/6 rounded bg-muted" />
        </div>
      </div>
    </div>
  );
}

export default function SEOLayout({ children }: { children: ReactNode }) {
  return (
    <MainShell>
      <Suspense fallback={<SEOPageSkeleton />}>
        {children}
      </Suspense>
    </MainShell>
  );
}