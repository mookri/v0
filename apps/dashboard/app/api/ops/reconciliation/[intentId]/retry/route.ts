import { NextRequest } from 'next/server';
import { proxyOpsPost } from '@/server/ops-bff';

export async function POST(
  request: NextRequest,
  context: { params: Promise<{ intentId: string }> },
) {
  const { intentId } = await context.params;
  return proxyOpsPost(
    request,
    `/api/v1/platform/ops/reconciliation/${encodeURIComponent(intentId)}:retry`,
    {},
  );
}

