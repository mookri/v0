import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { isFederated } from '@/server/federation';
import type { OtpResend } from '@navdhya/otp/schemas';

export const dynamic = 'force-dynamic';

/**
 * POST /api/auth/otp/resend — server-gated resend for customer app.
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (isFederated()) {
    // ADR-0020: the issuer is sole — the legacy ceremony does not exist
    // in federated deployments.
    return Response.json(err('NOT_FOUND', 'This deployment authenticates at the auth subdomain.', requestId), { status: 404 });
  }

  if (!laravelEnabled()) {
    return Response.json(err('DEPENDENCY_UNAVAILABLE', 'OTP service unavailable in mock mode.', requestId), { status: 503 });
  }

  let body: { challengeId?: string; challenge_id?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }

  const challengeId = body.challengeId ?? body.challenge_id;
  if (!challengeId) {
    return Response.json(err('VALIDATION_ERROR', 'challengeId is required.', requestId), { status: 422 });
  }

  try {
    const { data: resendResult } = await laravelPost<OtpResend>(
      null,
      '/customers/auth/otp/resend',
      {
        tenant_id: laravelTenant(),
        challenge_id: challengeId,
      },
      {},
      { timeout: 'fast', requestId },
    );

    return Response.json(ok(resendResult, requestId), {
      status: 202,
      headers: {
        'Cache-Control': 'no-store, max-age=0',
      },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to resend code.', requestId);
  }
}
