import type { NextConfig } from 'next';
import * as path from 'path';

const nextConfig: NextConfig = {
  // Windows dev box lacks symlink privileges for standalone trace-copy;
  // NEXT_STANDALONE=0 for local runs (same escape as the customer app).
  output: process.env['NEXT_STANDALONE'] === '0' ? undefined : 'standalone',
  outputFileTracingRoot: path.join(__dirname, '..', '..'),
  compress: true,
  env: {
    NEXT_PUBLIC_SITE_URL: 'https://dashboard.navdhya.com',
    NEXT_PUBLIC_PRODUCT: 'dashboard',
  },
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          { key: 'X-Frame-Options', value: 'SAMEORIGIN' },
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'Referrer-Policy', value: 'origin-when-cross-origin' },
          { key: 'X-Robots-Tag', value: 'noindex, nofollow' },
        ],
      },
    ];
  },
};

export default nextConfig;
