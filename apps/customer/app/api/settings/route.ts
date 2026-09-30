import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { customerSettingsSections } from '@/application/settings/settings';

export async function GET() {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.settings', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  return Response.json(ok({ sections: customerSettingsSections }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=60' },
  });
}
