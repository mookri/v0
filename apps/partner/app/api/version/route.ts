import { newRequestId, ok } from '@/application/api-envelope';

export async function GET() {
  const requestId = newRequestId();
  return Response.json(
    ok(
      {
        product: 'partner',
        version: process.env['APP_VERSION'] ?? 'dev',
        commitSha: process.env['GIT_SHA'] ?? 'dev',
        buildAt: process.env['BUILD_AT'] ?? 'unknown',
        environment: process.env['APP_ENV'] ?? process.env.NODE_ENV ?? 'development',
      },
      requestId,
    ),
    { headers: { 'Cache-Control': 'no-store' } },
  );
}
