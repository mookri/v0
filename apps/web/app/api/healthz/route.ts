/**
 * GET /api/healthz — liveness probe for the web BFF (Route 53 health
 * checks ping this every 30s, see lib/infra/dns.ts). Static truth only:
 * the process answers. Backend dependency readiness lives in /api/readyz
 * so a Laravel outage never flaps liveness (the BFF still serves
 * marketing + mock flows).
 *
 * Cache posture: route handlers default to per-request under
 * nextConfig.cacheComponents (segment config exports are disallowed) —
 * exactly what a probe wants.
 */
export async function GET() {
  return Response.json({
    status: 'ok',
    service: 'web-bff',
    ts: new Date().toISOString(),
  });
}
