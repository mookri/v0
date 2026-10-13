import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

interface LaravelPhoneIntent {
  intent_id: string;
  expires_in: number;
}

/** Backend rule (signup parity): prefix-optional, 6–15 digits. */
const PHONE_RULE = /^\+?[0-9]{6,15}$/;

/**
 * POST /api/auth/firebase/intent — record the single-use phone-login
 * intent (S50 kit; backend customers/auth/firebase/intent). Same
 * contract as the web console's route; the backend throttle
 * (throttle:10,1) is the real lock.
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { phone?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  const phone = typeof body.phone === 'string' ? body.phone.trim() : '';
  if (!PHONE_RULE.test(phone)) {
    return Response.json(err('VALIDATION_ERROR', 'Enter a valid mobile number.', requestId), { status: 422 });
  }

  try {
    const { data: intent } = await laravelPost<LaravelPhoneIntent>(
      null,
      '/customers/auth/firebase/intent',
      { tenant_id: laravelTenant(), phone },
    );

    return Response.json(
      ok({ intentId: intent.intent_id, expiresIn: intent.expires_in }, requestId),
      { status: 202 },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to start verification.', requestId);
  }
}
