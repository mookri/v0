import type React from 'react';

export const metadata = {
  title: 'Navdhya Partner',
  description: 'Receive, accept, and execute service work.',
  robots: 'noindex, nofollow',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body data-product="partner">{children}</body>
    </html>
  );
}
