import { newRequestId, ok } from '@/application/api-envelope';

export async function GET() {
  const requestId = newRequestId();
  return Response.json(ok({ status: 'ok', product: 'customer' }, requestId), {
    headers: { 'Cache-Control': 'no-store' },
  });
}
