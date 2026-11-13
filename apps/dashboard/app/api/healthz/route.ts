/**
 * GET /api/healthz — liveness probe for dashboard BFF.
 */
export async function GET() {
  return Response.json(
    {
      status: 'ok',
      service: 'dashboard-bff',
      ts: new Date().toISOString(),
    },
    {
      headers: { 'Cache-Control': 'no-store' },
    },
  );
}

