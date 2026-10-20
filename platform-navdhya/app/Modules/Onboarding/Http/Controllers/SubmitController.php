<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\PartnerApplication;
use App\Modules\Onboarding\TrackCatalog;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpDeliveryPolicy;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpRateLimitedException;
use App\Platform\Identity\Otp\OtpVerifyStatus;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Two-phase submit (stage 2 port): begin validates the draft against the
 * pinned track version, resolves the contact phone from the identity
 * evidence step, and issues an OTP challenge for it (MSG91 widget — the
 * code is delivered and judged in the browser, the runtime only ever
 * sees the token); resend grants a server-gated retry; confirm
 * re-validates, redeems the token, and transitions to SUBMITTED.
 * Wrong/expired/exhausted/replayed tokens collapse to one 401
 * (no oracle). The challenge binds to the application via its payload,
 * so it cannot be replayed across applications.
 */
final class SubmitController
{
    private const PURPOSE = 'partner_submit';

    public function __construct(private readonly OtpChallengeManager $otp)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    private function ownApplication(Request $request, string $id): PartnerApplication|JsonResponse
    {
        $application = PartnerApplication::query()->find($id);

        if ($application === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Application not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if ((string) $application->identity_id !== (string) $request->user()->getAuthIdentifier()) {
            return new JsonResponse([
                'error' => ['code' => 'permission_missing', 'message' => 'Not allowed.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        return $application;
    }

    /**
     * @return list<string>
     */
    public static function missingSteps(PartnerApplication $application): array
    {
        $track = TrackCatalog::find($application->track, $application->track_version);
        if ($track === null) {
            return ['unknown-track'];
        }

        $evidence = $application->evidence ?? [];
        $missing = [];
        foreach ($track['steps'] as $step) {
            if (! $step['required']) {
                continue;
            }
            $value = $evidence[$step['id']] ?? null;
            if ($value === null || $value === '' || $value === []) {
                $missing[] = $step['id'];
            }
        }

        return $missing;
    }

    /**
     * The OTP identifier is the phone the applicant declared in the
     * identity evidence step (its only home — partner_applications has
     * no contact columns), normalized to e164 so the MSG91-verified
     * digits and this value always canonicalize to the same number.
     */
    private function contactPhone(PartnerApplication $application): ?string
    {
        $phone = ($application->evidence ?? [])['identity']['phone'] ?? null;

        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        return $digits === '' ? null : '+'.$digits;
    }

    public function begin(Request $request, string $id): JsonResponse
    {
        $found = $this->ownApplication($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        $application = $found;

        if (! in_array($application->status, [PartnerApplication::STATUS_DRAFT, PartnerApplication::STATUS_WITHDRAWN], true)) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot submit in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $missing = self::missingSteps($application);
        if ($missing !== []) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Steps are still missing.', 'details' => ['fields' => ['steps' => $missing]], 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $phone = $this->contactPhone($application);
        if ($phone === null) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'A contact phone number is required to submit.',
                    'details' => ['fields' => ['identity.phone' => ['required']]],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        try {
            $challenge = $this->otp->issue(
                (string) app(TenantContext::class)->tenantId(),
                self::PURPOSE,
                $phone,
                CarbonImmutable::parse($this->nowIso()),
                ['application_id' => (string) $application->getKey()],
            );
        } catch (OtpRateLimitedException $e) {
            $response = new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
            ], 429);

            if ($e->retryAfterSeconds !== null && $e->retryAfterSeconds > 0) {
                $response->header('Retry-After', (string) $e->retryAfterSeconds);
            }

            return $response;
        } catch (OtpProviderUnavailableException $e) {
            report($e);

            return new JsonResponse([
                'error' => ['code' => 'dependency_unavailable', 'message' => 'Verification is temporarily unavailable. Try again shortly.', 'request_id' => $this->requestId($request)],
            ], 503);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $application->getKey(),
                'object' => 'submit_challenge',
                'challenge_id' => $challenge->id,
                'expires_in' => OtpDeliveryPolicy::load()->ttlSeconds,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Server-gated resend on a live submit challenge: the compounding
     * schedule (60s → 180s → 540s, max 3) is validated against the row
     * before the client may call the widget's retryOtp.
     */
    public function resend(Request $request, string $id): JsonResponse
    {
        $found = $this->ownApplication($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        $validated = $request->validate(['challenge_id' => ['required', 'string']]);

        try {
            $result = $this->otp->resend(
                (string) app(TenantContext::class)->tenantId(),
                self::PURPOSE,
                $validated['challenge_id'],
                CarbonImmutable::parse($this->nowIso()),
            );
        } catch (OtpRateLimitedException $e) {
            $response = new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
            ], 429);

            if ($e->retryAfterSeconds !== null && $e->retryAfterSeconds > 0) {
                $response->header('Retry-After', (string) $e->retryAfterSeconds);
            }

            return $response;
        }

        if ($result === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        return new JsonResponse([
            'data' => [
                'object' => 'otp_resend',
                'channel' => $result->channel,
                'channels' => $result->channels,
                'resends_left' => $result->resendsLeft,
                'next_allowed_at' => $result->nextAllowedAt?->toIso8601String(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 202);
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $found = $this->ownApplication($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        $application = $found;

        if (! in_array($application->status, [PartnerApplication::STATUS_DRAFT, PartnerApplication::STATUS_WITHDRAWN], true)) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot submit in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $validated = $request->validate([
            'challenge_id' => ['required', 'string'],
            'token' => ['required', 'string', 'max:4096'],
        ]);

        if (self::missingSteps($application) !== []) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Steps are still missing.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        try {
            $result = $this->otp->verify(
                (string) app(TenantContext::class)->tenantId(),
                [self::PURPOSE],
                $validated['challenge_id'],
                $validated['token'],
                CarbonImmutable::parse($this->nowIso()),
            );
        } catch (OtpProviderUnavailableException $e) {
            // Outage during judging: the attempt bump rolls back with
            // the transaction — an outage never burns an attempt.
            report($e);

            return new JsonResponse([
                'error' => ['code' => 'dependency_unavailable', 'message' => 'Verification is temporarily unavailable. Try again shortly.', 'request_id' => $this->requestId($request)],
            ], 503);
        }

        if ($result->status !== OtpVerifyStatus::Match) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        // The challenge is bound to this application: a token issued for
        // another application's phone verifies structurally but applies
        // to nothing.
        $payload = $result->challenge->payload ?? [];
        if (($payload['application_id'] ?? '') !== (string) $application->getKey()) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $application->forceFill([
            'status' => PartnerApplication::STATUS_SUBMITTED,
            'submitted_at' => $this->nowIso(),
        ])->save();

        return new JsonResponse([
            'data' => ['id' => (string) $application->getKey(), 'object' => 'partner_application', 'status' => $application->status],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function nowIso(): string
    {
        return gmdate('Y-m-d\TH:i:s.v\Z');
    }
}
