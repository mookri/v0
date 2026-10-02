import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

interface LaravelChallenge {
  challenge_id: string;
  expires_in: number;
}

// Wired OTP request (CUSTOMER_BACKEND=laravel): requests OTP challenge
// from backend for the given identity and purpose.
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { identity?: string; purpose?: 'login' | 'signup' } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  if (!body.identity || !body.purpose) {
    return Response.json(err('VALIDATION_ERROR', 'identity and purpose are required.', requestId), { status: 422 });
  }

  try {
    const tenant = laravelTenant();
    const { data: challenge } = await laravelPost<LaravelChallenge>(null, '/customers/auth/otp', {
      tenant_id: tenant,
      purpose: body.purpose,
      identity: body.identity,
    });

    return Response.json(
      ok({ challengeId: challenge.challenge_id, expiresIn: challenge.expires_in }, requestId),
      { status: 202 },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to send code.', requestId);
  }
}