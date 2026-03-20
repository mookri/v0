import { join } from 'path';
import { readFileSync } from 'fs';
import type { NextConfig } from 'next';

// ─── Redirects config ─────────────────────────────────────────────────────────
const redirectsConfig = JSON.parse(
  readFileSync(join(process.cwd(), 'routes-redirects.json'), 'utf8')
);

const nextConfig: NextConfig = {

  // ── Build ──────────────────────────────────────────────────────────────────
  typescript: {
    ignoreBuildErrors: false,
  },

  // ── Output ────────────────────────────────────────────────────────────────
  output: 'standalone',

  // ── Compression ───────────────────────────────────────────────────────────
  compress: true,

  // ── Environment ───────────────────────────────────────────────────────────
  env: {
    NEXT_PUBLIC_SITE_URL: 'https://www.navdhya.com',
  },

  // ── Image optimisation ────────────────────────────────────────────────────
  images: {
    /**
     * CHANGED: was `unoptimized: true`.
     *
     * Turning optimisation ON unlocks:
     *   • Auto WebP/AVIF per browser Accept header — no code changes needed
     *   • Responsive srcsets from a single source file
     *   • blurDataURL blur-up placeholder (used by HeroImage component)
     *   • On-demand resizing — only the exact size the browser requests
     *
     * Roll back: set  unoptimized: true  to restore old behaviour.
     * The rest of the image config becomes informational when unoptimized is true.
     */
    unoptimized: false,

    /**
     * AVIF first — 30–50% smaller than WebP at equivalent quality.
     * Next.js negotiates format via Accept header automatically.
     */
    formats: ['image/avif', 'image/webp'],

    // ── Breakpoints ──────────────────────────────────────────────────────────
    deviceSizes: [480, 640, 750, 828, 1080, 1200, 1920, 2048, 3840],
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384],

    /**
     * Set allowed quality tiers.
     */
    qualities: [75, 85, 90],

    /**
     * 7-day TTL for optimised images.
     * Hero images are filename-versioned by ID in hero-images.json,
     * so long TTLs are safe — new images use new filenames.
     */
    minimumCacheTTL: 60 * 60 * 24 * 7,

    // ── Remote patterns ───────────────────────────────────────────────────────
    remotePatterns: [
      // ── Placeholder service (picsum) ──────────────────────────────────────
      {
        protocol: 'https',
        hostname: 'picsum.photos',
      },

      // ── Navdhya production ─────────────────────────────────────────────────
      {
        protocol: 'https',
        hostname: 'www.navdhya.com',
      },
      {
        protocol: 'https',
        hostname: 'navdhya.com',
      },

      // ── Hero image CDN ─────────────────────────────────────────────────────
      // Set NEXT_PUBLIC_CDN_URL=https://cdn.navdhya.com in .env.production
      // heroImagePool.ts will prepend this to all /images/hero/* paths.
      {
        protocol: 'https',
        hostname: 'cdn.navdhya.com',
        pathname: '/images/hero/**',
      },

      // ── AWS S3 — uncomment + replace region/bucket ─────────────────────────
      // {
      //   protocol: 'https',
      //   hostname: 'navdhya-assets.s3.ap-south-1.amazonaws.com',
      //   pathname: '/hero/**',
      // },

      // ── Cloudflare R2 — uncomment + replace account hash ──────────────────
      // {
      //   protocol: 'https',
      //   hostname: '<ACCOUNT_HASH>.r2.cloudflarestorage.com',
      //   pathname: '/navdhya-assets/hero/**',
      // },

      // ── DigitalOcean Spaces ────────────────────────────────────────────────
      // {
      //   protocol: 'https',
      //   hostname: 'navdhya.sgp1.digitaloceanspaces.com',
      //   pathname: '/hero/**',
      // },

      // ── Local development ─────────────────────────────────────────────────
      {
        protocol: 'http',
        hostname: 'localhost',
      },
    ],
  },

  // ── Redirects ─────────────────────────────────────────────────────────────
  async redirects() {
    return redirectsConfig.redirects;
  },

  // ── Rewrites ──────────────────────────────────────────────────────────────
  async rewrites() {
    return [
      // { source: '/api/:path*',  destination: 'https://api.navdhya.com/:path*' },
      // { source: '/blog/:path*', destination: 'https://blog.navdhya.com/:path*' },
    ];
  },

  // ── Headers ───────────────────────────────────────────────────────────────
  async headers() {
    return [
      // ── Security — all routes ──────────────────────────────────────────────
      {
        source: '/:path*',
        headers: [
          { key: 'X-DNS-Prefetch-Control', value: 'on' },
          { key: 'Strict-Transport-Security', value: 'max-age=63072000; includeSubDomains; preload' },
          { key: 'X-Frame-Options', value: 'SAMEORIGIN' },
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'Referrer-Policy', value: 'origin-when-cross-origin' },
          { key: 'X-XSS-Protection', value: '1; mode=block' },
          {
            key: 'Permissions-Policy',
            value: 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
          },
        ],
      },

      // ── Hero images — immutable, 1 year ───────────────────────────────────
      // Safe because images are content-addressed by ID in hero-images.json.
      // New images always get new IDs — no stale cache risk.
      {
        source: '/images/hero/:file*',
        headers: [
          { key: 'Cache-Control', value: 'public, max-age=31536000, immutable' },
          { key: 'Vary', value: 'Accept' }, // distinct cache entry per format
        ],
      },

      // ── Other static images — 1 day + SWR 7 days ──────────────────────────
      {
        source: '/images/:path*',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=86400, stale-while-revalidate=604800',
          },
        ],
      },
    ];
  },
};

export default nextConfig;