import { NextRequest } from 'next/server';
import { proxyOpsPost } from '@/server/ops-bff';

export async function POST(
  request: NextRequest,
  context: { params: Promise<{ payoutId: string }> },
) {
  const { payoutId } = await context.params;
  const idempotencyKey = request.headers.get('idempotency-key')?.trim() ?? '';
  if (!idempotencyKey) {
    return Response.json(
      { error: 'idempotency_key_required', message: 'Idempotency-Key header is required.' },
      { status: 400 },
    );
  }

  const body = (await request.json().catch(() => ({}))) as Record<string, unknown>;
  return proxyOpsPost(
    request,
    `/api/v1/platform/ops/payouts/${encodeURIComponent(payoutId)}:approve`,
    body,
    { idempotencyKey },
  );
}

