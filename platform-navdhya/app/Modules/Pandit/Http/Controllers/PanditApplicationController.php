<?php

namespace App\Modules\Pandit\Http\Controllers;

use App\Modules\Onboarding\Contracts\ManagesPartnerApplications;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pandit application surface (pandit track intake only).
 *
 * The Onboarding module owns the track lifecycle — steps, OTP-gated submit
 * and the capability-gated review queue — so this controller just starts
 * the applicant's pandit-track application through the port and
 * materializes the PENDING pandit profile it will need once an admin
 * verifies it. No decision verbs here: reviews live on /api/v1/onboarding.
 */
final class PanditApplicationController
{
    /** @var list<string> states that mean an application is still live */
    private const LIVE_STATES = [
        'DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED',
    ];

    public function __construct(
        private readonly AuditWriter $audit,
        private readonly ManagesPartnerApplications $applications,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    private function actor(Request $request): ?\App\Models\User
    {
        $user = $request->user();

        return $user instanceof \App\Models\User ? $user : null;
    }

    // SELF: start (or re-open) the caller's pandit-track application
    public function store(Request $request): JsonResponse
    {
        $user = $this->actor($request);

        if ($user === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $validated = $request->validate([
            'track' => ['required', 'string', 'in:pandit'],
            'market' => ['sometimes', 'string', 'max:16'],
        ]);

        $identityId = (string) $user->getAuthIdentifier();
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $market = (string) ($validated['market'] ?? 'in');

        $existing = $this->applications->latestForIdentity($identityId, 'pandit');

        if ($existing !== null && in_array($existing['status'], self::LIVE_STATES, true)) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_APPLICATION_EXISTS', 'message' => 'You already have a live pandit application.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $application = $this->applications->start($identityId, 'pandit');

        $profile = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        $profileCreated = false;

        if ($profile === null) {
            // Fail-closed: the profile lands PENDING and only an admin
            // verification makes it publicly visible.
            $profile = new PanditProfile();
            $profile->forceFill([
                'tenant_id' => $tenantId,
                'user_id' => $user->getAuthIdentifier(),
                'display_name' => (string) $user->name,
                'market' => $market,
                'verification_status' => PanditProfile::VERIFICATION_PENDING,
                'status' => PanditProfile::STATUS_ACTIVE,
            ]);
            $profile->save();
            $profileCreated = true;

            $this->audit->record(
                'pandit.profile.provisioned',
                ['module' => 'pandit', 'resource_type' => 'pandit_profile', 'resource_id' => (string) $profile->getKey()],
                null,
                ['user_id' => $identityId, 'source' => 'application', 'market' => $market],
            );
        }

        $this->audit->record(
            'pandit.application.started',
            ['module' => 'pandit', 'resource_type' => 'partner_application', 'resource_id' => $application['id']],
            null,
            ['user_id' => $identityId, 'track' => 'pandit', 'track_version' => $application['track_version']],
        );

        return new JsonResponse([
            'data' => [
                'application' => $application,
                'pandit_profile' => [
                    'id' => (string) $profile->getKey(),
                    'verification_status' => $profile->verification_status,
                    'status' => $profile->status,
                    'created' => $profileCreated,
                ],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    // SELF: read the caller's pandit-track application
    public function own(Request $request): JsonResponse
    {
        $user = $this->actor($request);

        if ($user === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $application = $this->applications->latestForIdentity((string) $user->getAuthIdentifier(), 'pandit');

        if ($application === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_APPLICATION_NOT_FOUND', 'message' => 'No pandit application found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $application,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
