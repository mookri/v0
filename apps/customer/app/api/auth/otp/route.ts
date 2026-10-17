import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { isFederated } from '@/server/federation';

export const dynamic = 'force-dynamic';

interface LaravelChallenge {
  challenge_id: string;
  expires_in: number;
}

/**
 * POST /api/auth/otp — request an OTP challenge.
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

  let body: { identity?: string; purpose?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }

  if (!body.identity) {
    return Response.json(err('VALIDATION_ERROR', 'identity is required.', requestId), { status: 422 });
  }

  try {
    const { data: challenge } = await laravelPost<LaravelChallenge>(
      null,
      '/customers/auth/otp',
      {
        tenant_id: laravelTenant(),
        purpose: body.purpose ?? 'login',
        identity: body.identity,
      },
      {},
      { timeout: 'fast', requestId },
    );

    return Response.json(
      ok(
        {
          challengeId: challenge.challenge_id,
          expiresIn: challenge.expires_in,
        },
        requestId,
      ),
      { status: 202 },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to send code.', requestId);
  }
}
