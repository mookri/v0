import type React from 'react';
import { EntitySwitcher } from '@/components/EntitySwitcher';

export const metadata = {
  title: 'Navdhya Partner',
  description: 'Receive, accept, and execute service work.',
  robots: 'noindex, nofollow',
  manifest: '/manifest.webmanifest',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  const tenantLabel = process.env.PARTNER_TENANT_LABEL ?? process.env.NEXT_PUBLIC_TENANT_LABEL ?? 'Active Partner Org';

  return (
    <html lang="en">
      <body data-product="partner" className="min-h-screen bg-[#FFF7ED] text-[#2A2421] antialiased">
        <EntitySwitcher tenantLabel={tenantLabel} />
        <div className="mx-auto max-w-6xl px-4 py-6">{children}</div>
      </body>
    </html>
  );
}
