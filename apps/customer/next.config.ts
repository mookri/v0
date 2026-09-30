import type { NextConfig } from 'next';

import * as path from 'path';

const nextConfig: NextConfig = {
  // Standalone packaging requires symlink privileges the Windows dev box
  // lacks (EPERM in trace-copy). Local staging runs use NEXT_STANDALONE=0;
  // deploy pipelines keep the default standalone output.
  output: process.env['NEXT_STANDALONE'] === '0' ? undefined : 'standalone',
  // Pin tracing root: a stray lockfile above the repo confuses Next's
  // workspace-root inference otherwise.
  outputFileTracingRoot: path.join(__dirname, '..', '..'),
  compress: true,
  env: {
    NEXT_PUBLIC_SITE_URL: 'https://app.navdhya.com',
    NEXT_PUBLIC_PRODUCT: 'customer',
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
