import { NextRequest } from 'next/server';
import { proxyOpsGet } from '@/server/ops-bff';

export async function GET(request: NextRequest) {
  return proxyOpsGet(request, '/api/v1/platform/billing');
}

