/**
 * GET /api/healthz — liveness probe for partner BFF.
 */
export async function GET() {
  return Response.json(
    {
      status: 'ok',
      service: 'partner-bff',
      ts: new Date().toISOString(),
    },
    {
      headers: { 'Cache-Control': 'no-store' },
    },
  );
}

