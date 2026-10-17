import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { isFederated } from '@/server/federation';
import type { OtpConfig } from '@navdhya/otp/schemas';


/**
 * GET /api/auth/otp/config — public widget bootstrap for customer app.
 */
export async function GET(req: Request) {
  const requestId = newRequestId();

  if (isFederated()) {
    // ADR-0020: the issuer is sole — the legacy ceremony does not exist
    // in federated deployments.
    return Response.json(err('NOT_FOUND', 'This deployment authenticates at the auth subdomain.', requestId), { status: 404 });
  }

  if (!laravelEnabled()) {
    return Response.json(err('DEPENDENCY_UNAVAILABLE', 'OTP service unavailable in mock mode.', requestId), { status: 503 });
  }

  try {
    const { data: config } = await laravelGet<OtpConfig>(
      null,
      '/customers/auth/otp/config',
    );

    return Response.json(ok(config, requestId), {
      status: 200,
      headers: {
        'Cache-Control': 'no-store, max-age=0',
      },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch OTP configuration.', requestId);
  }
}
