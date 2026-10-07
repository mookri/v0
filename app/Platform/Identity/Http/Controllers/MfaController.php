<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Models\User;
use App\Platform\Api\WebMutations;
use App\Platform\Identity\Events\MfaEnrolled;
use App\Platform\Identity\Events\MfaRevoked;
use App\Platform\Identity\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * TOTP second factor (SEC-004 §1.4): enroll (otpauth URL out, secret
 * stored disabled) → confirm (code in, recovery codes out once) →
 * verify (the step-up primitive shared by /api/v1/identity/mfa/verify
 * and the web /oauth/stepup form) → revoke (live code required).
 * Machines denied; code failures collapse to one 401.
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

    // Shared actions (doc 06 §7.1-7.2): the security page's forms and
    // /api/v1/identity/mfa point here — one enrollment/confirmation path,
    // negotiation only. One-time values (setup key, recovery codes) exist
    // only inside the envelope, so the web flash derives from the success
    // response and dies with that single render; API envelopes stay
    // byte-identical (MfaTest + the full suite are the parity pin).
    public function enroll(Request $request): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->enrollJson($request),
            static function (JsonResponse $result): string {
                $url = WebMutations::stringData($result, 'otpauth_url');
                $secret = '';
                $query = parse_url($url, PHP_URL_QUERY);
                if (is_string($query)) {
                    parse_str($query, $params);
                    if (isset($params['secret']) && is_string($params['secret'])) {
                        $secret = $params['secret'];
                    }
                }

                return 'Authenticator setup key — enter this key in your app, then confirm below: '.$secret;
            },
        );
    }

    private function enrollJson(Request $request): JsonResponse
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

    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->confirmJson($request),
            static fn (JsonResponse $result): string => 'Authenticator app enabled. Recovery codes (shown once — store them safely): '
                .implode(', ', WebMutations::listData($result, 'recovery_codes')),
        );
    }

    private function confirmJson(Request $request): JsonResponse
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

    // Shared action (doc 06 §7.1-7.2): /api/v1/identity/mfa/verify and
    // the web /oauth/stepup form both land here — one verification, one
    // proof row, negotiation only at the seam (web returns to the
    // sanitized return_to carrying a coded flash; API keeps its envelope
    // untouched, byte-identical to before).
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->actor($request);
        if ($user instanceof JsonResponse) {
            return WebMutations::negotiate($request, $user, '');
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        if (! $this->mfa->verify($user, $validated['code'], time())) {
            return WebMutations::negotiate($request, new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401), '');
        }

        // Every successful verification is a fresh-possession proof for
        // step-up (SEC-004): the requires_acr gate reads these rows.
        $stepUp = new \App\Platform\Identity\Models\MfaStepUp();
        $stepUp->forceFill([
            'user_id' => $user->getAuthIdentifier(),
            'verified_at' => gmdate('Y-m-d H:i:s'),
            'ip' => $request->ip(),
        ]);
        $stepUp->save();

        return WebMutations::negotiate(
            $request,
            new JsonResponse([
                'data' => ['object' => 'mfa_verification', 'verified' => true],
                'meta' => ['request_id' => $this->requestId($request)],
            ]),
            'Identity verified — sensitive actions are unlocked for 15 minutes.',
            StepUpController::safeReturnTo($request->input('return_to')),
        );
    }

    public function revoke(Request $request): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->revokeJson($request),
            'Authenticator app revoked.',
        );
    }

    private function revokeJson(Request $request): JsonResponse
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

    public function recoveryCodes(Request $request): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->recoveryCodesJson($request),
            static fn (JsonResponse $result): string => 'New recovery codes (shown once — store them safely): '
                .implode(', ', WebMutations::listData($result, 'recovery_codes')),
        );
    }

    private function recoveryCodesJson(Request $request): JsonResponse
    {
        $user = $this->actor($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        $codes = $this->mfa->regenerateRecoveryCodes($user, $validated['code'], time());

        if ($codes === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        return new JsonResponse([
            'data' => ['object' => 'mfa_recovery_codes', 'recovery_codes' => $codes],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
