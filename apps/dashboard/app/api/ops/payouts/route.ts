import { NextRequest } from 'next/server';
import { proxyOpsGet, proxyOpsPost } from '@/server/ops-bff';

export async function GET(request: NextRequest) {
  return proxyOpsGet(request, '/api/v1/platform/ops/payouts');
}

export async function POST(request: NextRequest) {
  const body = (await request.json().catch(() => ({}))) as Record<string, unknown>;
  return proxyOpsPost(request, '/api/v1/platform/ops/payouts', body);
}

