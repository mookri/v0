import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import { readFileSync } from 'fs';
import type { NextConfig } from 'next';

// Read redirect configuration
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const redirectsConfig = JSON.parse(readFileSync(join(__dirname, 'routes-redirects.json'), 'utf8'));

const nextConfig: NextConfig = {
  // Build and development settings
  eslint: {
    ignoreDuringBuilds: false,
  },
  typescript: {
    ignoreBuildErrors: false,
  },
  
  // Advanced image optimization
  images: {
    // Since unoptimized is true, images won't be optimized by Next.js
    // but we're keeping the configuration for future use
    unoptimized: true,
    
    // Image optimization settings (will be used if unoptimized is set to false)
    deviceSizes: [640, 750, 828, 1080, 1200, 1920, 2048, 3840],
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384],
    
    // Domains from which images can be served
    domains: [
      'www.navdhya.com',
      'navdhya.com',
      'localhost',
    ],
    
    // Advanced image format support
    formats: ['image/webp', 'image/avif'],
    
    // Minimum cache TTL for optimized images
    minimumCacheTTL: 60,
  },
  
  // Advanced routing and redirects
  async redirects() {
    return redirectsConfig.redirects;
  },
  
  // Advanced rewrites for proxying or path manipulation
  async rewrites() {
    return [
      // Example rewrites for API endpoints or external services
      // {
      //   source: '/api/:path*',
      //   destination: 'https://api.navdhya.com/:path*'
      // },
      // {
      //   source: '/blog/:path*',
      //   destination: 'https://blog.navdhya.com/:path*'
      // }
    ];
  },
  
  // Advanced headers for security and performance
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          {
            key: 'X-DNS-Prefetch-Control',
            value: 'on'
          },
          {
            key: 'Strict-Transport-Security',
            value: 'max-age=63072000; includeSubDomains; preload'
          },
          {
            key: 'X-Frame-Options',
            value: 'SAMEORIGIN'
          },
          {
            key: 'X-Content-Type-Options',
            value: 'nosniff'
          },
          {
            key: 'Referrer-Policy',
            value: 'origin-when-cross-origin'
          },
          {
            key: 'X-XSS-Protection',
            value: '1; mode=block'
          },
          {
            key: 'Permissions-Policy',
            value: 'camera=(), microphone=(), geolocation=(), interest-cohort=()'
          }
        ]
      }
    ];
  },
  
  // Environment variables
  env: {
    // Add any environment-specific configurations here
    NEXT_PUBLIC_SITE_URL: 'https://www.navdhya.com',
  },
  
  // Advanced output configuration
  output: 'standalone',
  
  // Advanced compression settings
  compress: true,
};

export default nextConfig;