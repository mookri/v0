<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Models\User;
use App\Platform\Identity\Events\MfaEnrolled;
use App\Platform\Identity\Events\MfaRevoked;
use App\Platform\Identity\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TOTP second factor (SEC-004 §1.4): enroll (otpauth URL out, secret
 * stored disabled) → confirm (code in, recovery codes out once) →
 * verify (step-up primitive for /oauth/stepup later) → revoke (live
 * code required). Machines denied; code failures collapse to one 401.
 * User-level surface: no tenant scope, no audit rows (identity
 * self-service precedent) — lifecycle emits platform events.
 */
final class MfaController
{
    public function __construct(private readonly MfaService $mfa)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    private function actor(Request $request): User|JsonResponse
    {
        if ($request->user() instanceof \App\Platform\Identity\MachinePrincipal) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        return $user;
    }

    public function status(Request $request): JsonResponse
    {
        $user = $this->actor($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return new JsonResponse([
            'data' => [
                'object' => 'mfa_status',
                'enabled' => (bool) $user->mfa_enabled,
                'enrolled_at' => $user->mfa_enrolled_at,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function enroll(Request $request): JsonResponse
    {
        $user = $this->actor($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return new JsonResponse([
            'data' => array_merge(
                ['object' => 'mfa_enrollment'],
                $this->mfa->enroll($user)
            ),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function confirm(Request $request): JsonResponse
    {
        $user = $this->actor($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        $codes = $this->mfa->confirm($user, $validated['code'], time());

        if ($codes === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        event(new MfaEnrolled((string) $user->getAuthIdentifier()));

        return new JsonResponse([
            'data' => ['object' => 'mfa_enrollment', 'recovery_codes' => $codes],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $user = $this->actor($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        if (! $this->mfa->verify($user, $validated['code'], time())) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        return new JsonResponse([
            'data' => ['object' => 'mfa_verification', 'verified' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function revoke(Request $request): JsonResponse
    {
        $user = $this->actor($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        if (! $this->mfa->revoke($user, $validated['code'], time())) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        event(new MfaRevoked((string) $user->getAuthIdentifier()));

        return new JsonResponse([
            'data' => ['object' => 'mfa_revocation', 'revoked' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
