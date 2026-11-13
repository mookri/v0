/**
 * GET /api/healthz — liveness probe for customer BFF.
 */
export async function GET() {
  return Response.json(
    {
      status: 'ok',
      service: 'customer-bff',
      ts: new Date().toISOString(),
    },
    {
      headers: { 'Cache-Control': 'no-store' },
    },
  );
}

