<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\PartnerApplication;
use App\Modules\Onboarding\OtpRateLimitedException;
use App\Modules\Onboarding\OtpService;
use App\Modules\Onboarding\RandomOtpCodes;
use App\Modules\Onboarding\TrackCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Two-phase submit (stage 2 port): begin validates the draft against the
 * pinned track version and issues an OTP challenge; confirm re-validates,
 * verifies the code, and transitions to SUBMITTED. Wrong/expired/
 * exhausted/replayed codes collapse to one error (no oracle).
 */
final class SubmitController
{
    public function __construct(private readonly OtpService $otp)
    {
    }

    public static function withRandomCodes(): self
    {
        return new self(new OtpService(new RandomOtpCodes()));
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

        try {
            $challenge = $this->otp->issue(
                (string) $application->identity_id,
                CarbonImmutable::parse($this->nowIso())
            );
        } catch (OtpRateLimitedException) {
            return new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
            ], 429);
        }

        return new JsonResponse([
            'data' => ['id' => (string) $application->getKey(), 'object' => 'submit_challenge', 'challenge_id' => (string) $challenge->getKey()],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
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
            'code' => ['required', 'string'],
        ]);

        if (self::missingSteps($application) !== []) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Steps are still missing.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $verified = $this->otp->verify($validated['challenge_id'], $validated['code'], CarbonImmutable::parse($this->nowIso()));

        if (! $verified) {
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
