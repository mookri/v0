import type React from 'react';

export const metadata = {
  title: 'Navdhya Customer',
  description: 'Your rituals, bookings, and profile.',
  robots: 'noindex, nofollow',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
