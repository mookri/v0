<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\OtpRateLimitedException;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Modules\Customers\CustomerActivityRecorder;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contact verification (B3): status read plus OTP-proven phone/email
 * changes. Challenges bind identifier = the new value and payload user_id
 * = the requester; confirm enforces both, so a code cannot be replayed
 * across accounts. Re-verifying an already-verified current value is a
 * 409 (nothing to prove), and a contact already owned by another
 * account is a 409 (unique-collision guard on the write).
 */
final class CustomerVerificationController extends CustomerSelfServiceController
{
    /** @var list<string> */
    private const VERIFY_PURPOSES = [
        CustomerOtpChallenge::PURPOSE_VERIFY_PHONE,
        CustomerOtpChallenge::PURPOSE_VERIFY_EMAIL,
        CustomerOtpChallenge::PURPOSE_CHANGE_PHONE,
        CustomerOtpChallenge::PURPOSE_CHANGE_EMAIL,
    ];

    public function __construct(
        private readonly CustomerOtpService $otp,
        private readonly AuditWriter $audit,
        private readonly CustomerActivityRecorder $activity,
    ) {
    }

    public function status(Request $request): JsonResponse
    {
        $found = $this->ownProfile($request);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        $user = $found['user'];

        return new JsonResponse([
            'data' => [
                'object' => 'contact_verification',
                'phone' => [
                    'value_masked' => self::maskPhone($user->phone),
                    'verified_at' => $user->phone_verified_at,
                ],
                'email' => [
                    'value_masked' => self::maskEmail($user->email),
                    'verified_at' => self::maskEmail($user->email) !== null ? $user->email_verified_at : null,
                ],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function request(Request $request): JsonResponse
    {
        $found = $this->ownProfile($request);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:phone,email'],
            'value' => ['required', 'string', 'max:190'],
        ]);

        $user = $found['user'];
        $channel = $validated['channel'];
        $value = $validated['value'];

        if ($channel === 'phone' && ! preg_match('/^\+?[0-9]{6,15}$/', $value)) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Invalid phone number.',
                    'details' => ['fields' => ['value' => ['invalid_phone']]],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        if ($channel === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Invalid email address.',
                    'details' => ['fields' => ['value' => ['invalid_email']]],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        $current = $channel === 'phone' ? $user->phone : $user->email;
        $verifiedAt = $channel === 'phone' ? $user->phone_verified_at : $user->email_verified_at;

        if ($value === $current && $verifiedAt !== null) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'This contact is already verified.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $changing = $value !== $current;
        $purpose = $channel === 'phone'
            ? ($changing ? CustomerOtpChallenge::PURPOSE_CHANGE_PHONE : CustomerOtpChallenge::PURPOSE_VERIFY_PHONE)
            : ($changing ? CustomerOtpChallenge::PURPOSE_CHANGE_EMAIL : CustomerOtpChallenge::PURPOSE_VERIFY_EMAIL);

        try {
            $challenge = $this->otp->issue(
                (string) app(TenantContext::class)->tenantId(),
                $purpose,
                $value,
                CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z')),
                ['user_id' => (string) $user->getAuthIdentifier(), 'channel' => $channel, 'value' => $value]
            );
        } catch (OtpRateLimitedException) {
            return new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
            ], 429);
        }

        return new JsonResponse([
            'data' => [
                'object' => 'otp_challenge',
                'challenge_id' => (string) $challenge->getKey(),
                'expires_in' => CustomerOtpService::TTL_SECONDS,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 202);
    }

    public function confirm(Request $request, string $challengeId): JsonResponse
    {
        $found = $this->ownProfile($request);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        $challenge = $this->otp->verifyForPurposes(
            (string) app(TenantContext::class)->tenantId(),
            self::VERIFY_PURPOSES,
            $challengeId,
            $validated['code'],
            CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))
        );

        if ($challenge === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $user = $found['user'];
        $payload = $challenge->payload ?? [];

        // The challenge is bound to its requester: a code issued for
        // another account verifies structurally but applies to nothing.
        if (($payload['user_id'] ?? null) !== (string) $user->getAuthIdentifier()) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $channel = (string) ($payload['channel'] ?? '');
        $value = (string) ($payload['value'] ?? '');

        if (($channel !== 'phone' && $channel !== 'email') || $value === '' || $value !== (string) $challenge->identifier) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $now = CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))->toDateTimeString();

        try {
            if ($channel === 'phone') {
                $user->forceFill(['phone' => $value, 'phone_verified_at' => $now])->save();
            } else {
                $user->forceFill(['email' => $value, 'email_verified_at' => $now])->save();
            }
        } catch (UniqueConstraintViolationException) {
            // F-SMOKE5: users.email / users.phone are globally unique, so a
            // contact that already belongs to another account is a
            // collision — a 409, not an unhandled unique-violation 500.
            // The challenge was consumed by verifyForPurposes above; the
            // caller requests a fresh one (same shape as signup's
            // duplicate-phone conflict).
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'This contact is already in use by another account.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $this->audit->record(
            'customers.verification.completed',
            ['module' => 'customers', 'resource_type' => 'buyer_profile', 'resource_id' => (string) $found['profile']->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'channel' => $channel]
        );
        $this->activity->record(
            (string) $user->getAuthIdentifier(),
            'verification',
            $channel === 'phone' ? 'Phone number verified' : 'Email address verified'
        );

        return new JsonResponse([
            'data' => [
                'object' => 'contact_verification',
                'channel' => $channel,
                'value_masked' => $channel === 'phone' ? self::maskPhone($value) : self::maskEmail($value),
                'verified_at' => $now,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
