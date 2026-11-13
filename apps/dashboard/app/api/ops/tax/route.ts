import { NextRequest } from 'next/server';
import { proxyOpsGet } from '@/server/ops-bff';

export async function GET(request: NextRequest) {
  const purpose = request.nextUrl.searchParams.get('purpose')?.trim() ?? '';
  if (purpose.length < 5) {
    return Response.json(
      {
        error: 'tax_purpose_required',
        message: 'Explicit filing or audit purpose (>= 5 characters) is required.',
      },
      { status: 422 },
    );
  }
  return proxyOpsGet(request, '/api/v1/platform/ops/tax', { purpose });
}

