import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import type { OtpConfig } from '@navdhya/otp/schemas';


/**
 * GET /api/auth/otp/config — public widget bootstrap.
 *
 * Pure proxy to Laravel GET /customers/auth/otp/config (no-store).
 * Returns widget_id, token_auth, otp digits/ttl, and compounding resend policy.
 */
export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Service unavailable.', requestId), { status: 401 });
  }

  try {
    const { data: config } = await laravelGet<OtpConfig>(
      null,
      '/customers/auth/otp/config',
      {},
      {},
      { timeout: 'fast', requestId },
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
