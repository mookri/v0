import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { isFederated } from '@/server/federation';

interface LaravelChallenge {
  challenge_id: string;
  expires_in: number;
}

// Wired signup intent (CUSTOMER_BACKEND=laravel): records intent
// backend-side and returns the OTP challenge. Without the flag the
// surface is unauthenticated-only (no mock accounts fabricated).
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (isFederated()) {
    // ADR-0020: the issuer is sole — the legacy ceremony does not exist
    // in federated deployments (verified proof auto-provisions there).
    return Response.json(err('NOT_FOUND', 'This deployment authenticates at the auth subdomain.', requestId), { status: 404 });
  }
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  let body: { phone?: string; name?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  if (!body.phone || !body.name) {
    return Response.json(err('VALIDATION_ERROR', 'phone and name are required.', requestId), { status: 422 });
  }
  try {
    const { data: challenge } = await laravelPost<LaravelChallenge>(null, '/customers/auth/signup', {
      tenant_id: laravelTenant(),
      phone: body.phone,
      name: body.name,
    });
    return Response.json(
      ok({ challengeId: challenge.challenge_id, expiresIn: challenge.expires_in }, requestId),
      { status: 202 },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Signup is unavailable.', requestId);
  }
}
