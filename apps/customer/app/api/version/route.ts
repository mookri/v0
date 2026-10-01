import { newRequestId, ok } from '@/application/api-envelope';

// Deployment metadata (§19 contract): version, commit, build time,
// environment. Public-safe by design — no secrets here, ever.
export function buildVersion(product: 'customer' | 'partner'): {
  product: string;
  version: string;
  commitSha: string;
  buildAt: string;
  environment: string;
} {
  return {
    product,
    version: process.env['APP_VERSION'] ?? 'dev',
    commitSha: process.env['GIT_SHA'] ?? 'dev',
    buildAt: process.env['BUILD_AT'] ?? 'unknown',
    environment: process.env['APP_ENV'] ?? process.env.NODE_ENV ?? 'development',
  };
}

export async function GET() {
  const requestId = newRequestId();
  return Response.json(ok(buildVersion('customer'), requestId), {
    headers: { 'Cache-Control': 'no-store' },
  });
}
