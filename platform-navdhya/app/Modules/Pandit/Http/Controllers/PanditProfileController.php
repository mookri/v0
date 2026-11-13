<?php

namespace App\Modules\Pandit\Http\Controllers;

use App\Domain\Verification\Projector\VerificationProjector;
use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pandit profile controller (public + self-serve + admin).
 */
final class PanditProfileController
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly ListsPanditProfiles $profiles,
        private readonly VerificationProjector $projector,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PUBLIC: List verified pandits
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'specialization' => ['sometimes', 'string', 'max:64'],
            'market' => ['sometimes', 'string', 'max:16'],
            'min_rating' => ['sometimes', 'numeric', 'min:0', 'max:5'],
            'tradition' => ['sometimes', 'string', 'max:32'],
            'gotra' => ['sometimes', 'string', 'max:64'],
            'shakha_code' => ['sometimes', 'string', 'max:32'],
        ]);

        $filters = $request->only(['specialization', 'market', 'min_rating', 'tradition', 'gotra', 'shakha_code']);
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->profiles->publicProfiles($filters, $limit, $cursor);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // PUBLIC: Show single pandit profile (only verified + active)
    public function show(Request $request, string $id): JsonResponse
    {
        $profile = $this->profiles->profileById($id);

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        // Only expose verified + active profiles publicly
        if ($profile['verification_status'] !== 'VERIFIED' || $profile['status'] !== 'ACTIVE') {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $profile,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // SELF: Get own profile
    public function own(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $profile = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit profile not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $this->serializeFull($profile),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // SELF: Update own profile (strict field allowlist)
    public function updateOwn(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $profile = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit profile not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        // Strict field allowlist (same pattern as CustomerProfileController)
        $allowed = [
            'display_name', 'bio', 'languages', 'specializations', 'experience_years',
            'certification_refs', 'base_rate_minor', 'currency', 'market',
            'tradition', 'gotra', 'shakha_code', 'travel_buffer_minutes', 'travel_radius_km', 'timezone',
        ];
        $unknown = array_diff(array_keys($request->all()), $allowed);
        if ($unknown !== []) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Unknown fields: '.implode(', ', $unknown).'.',
                    'details' => ['fields' => array_fill_keys($unknown, ['unknown_field'])],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        $validated = $request->validate([
            'display_name' => ['sometimes', 'string', 'max:190'],
            'bio' => ['nullable', 'string'],
            'languages' => ['sometimes', 'array'],
            'languages.*' => ['string', 'max:10'],
            'specializations' => ['sometimes', 'array'],
            'specializations.*' => ['string', 'max:64'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'certification_refs' => ['sometimes', 'array'],
            'certification_refs.*' => ['string', 'max:64'],
            'base_rate_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'market' => ['nullable', 'string', 'max:16'],
            'tradition' => ['nullable', 'string', new \Illuminate\Validation\Rules\Enum(\App\Modules\Pandit\Contracts\VedicTradition::class)],
            'gotra' => ['nullable', 'string', 'max:64'],
            'shakha_code' => ['nullable', 'string', 'max:32'],
            'travel_buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:360'],
            'travel_radius_km' => ['nullable', 'integer', 'min:0', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $profile->forceFill($validated)->save();

        $this->audit->record(
            'pandit.profile.updated',
            ['module' => 'pandit', 'resource_type' => 'pandit_profile', 'resource_id' => (string) $profile->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'fields' => array_keys($validated)]
        );

        return new JsonResponse([
            'data' => $this->serializeFull($profile),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Admin-scoped profile resolution (404 PANDIT_NOT_FOUND, never a bare
     * ModelNotFoundException, so every surface keeps the coded envelope).
     */
    private function adminProfile(Request $request, string $id): JsonResponse|PanditProfile
    {
        $profile = PanditProfile::query()
            ->where('tenant_id', (string) app(TenantContext::class)->tenantId())
            ->where('id', $id)
            ->first();

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return $profile;
    }

    private function adminActor(Request $request): string
    {
        return (string) $request->user()?->getAuthIdentifier();
    }

    // ADMIN: List all profiles (including unverified)
    public function adminIndex(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,SUSPENDED,DEACTIVATED'],
            'verification_status' => ['sometimes', 'string', 'in:PENDING,DOCS_SUBMITTED,INTERVIEW_SCHEDULED,VERIFIED,REJECTED'],
            'search' => ['sometimes', 'string', 'max:190'],
        ]);

        $filters = $request->only(['status', 'verification_status', 'search']);
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->profiles->adminProfiles($filters, $limit, $cursor);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // ADMIN: Verify profile (capability: pandit:profile:manage)
    public function verify(Request $request, string $id): JsonResponse
    {
        $profile = $this->adminProfile($request, $id);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        if ($profile->verification_status === PanditProfile::VERIFICATION_VERIFIED) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Pandit is already verified.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        // Verification is its own axis: suspension survives it (reactivate
        // is a separate, separately audited decision).
        $claim = $this->projector->grantManualClaimAndProject(
            subject: $profile,
            subjectType: 'pandit',
            actorId: $this->adminActor($request),
            metadata: ['source' => 'pandit.admin.verify'],
        );

        $this->audit->record(
            'pandit.profile.verified',
            ['module' => 'pandit', 'resource_type' => 'pandit_profile', 'resource_id' => (string) $profile->getKey()],
            null,
            ['admin_user_id' => $this->adminActor($request), 'claim_id' => (string) $claim->getKey(), 'mode' => $claim->mode]
        );

        return new JsonResponse([
            'data' => $this->serializeFull($profile),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: Suspend profile (capability: pandit:profile:manage)
    public function suspend(Request $request, string $id): JsonResponse
    {
        $profile = $this->adminProfile($request, $id);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        if ($profile->status === PanditProfile::STATUS_SUSPENDED) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Pandit is already suspended.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $profile->forceFill(['status' => PanditProfile::STATUS_SUSPENDED])->save();

        $this->audit->record(
            'pandit.profile.suspended',
            ['module' => 'pandit', 'resource_type' => 'pandit_profile', 'resource_id' => (string) $profile->getKey()],
            null,
            ['admin_user_id' => $this->adminActor($request)]
        );

        return new JsonResponse([
            'data' => $this->serializeFull($profile),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: Reactivate profile (capability: pandit:profile:manage)
    public function reactivate(Request $request, string $id): JsonResponse
    {
        $profile = $this->adminProfile($request, $id);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        if ($profile->status === PanditProfile::STATUS_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Pandit is already active.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $profile->forceFill(['status' => PanditProfile::STATUS_ACTIVE])->save();

        $this->audit->record(
            'pandit.profile.reactivated',
            ['module' => 'pandit', 'resource_type' => 'pandit_profile', 'resource_id' => (string) $profile->getKey()],
            null,
            ['admin_user_id' => $this->adminActor($request)]
        );

        return new JsonResponse([
            'data' => $this->serializeFull($profile),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFull(PanditProfile $profile): array
    {
        return [
            'id' => (string) $profile->getKey(),
            'display_name' => $profile->display_name,
            'tradition' => $profile->tradition instanceof \App\Modules\Pandit\Contracts\VedicTradition ? $profile->tradition->value : (is_string($profile->tradition) ? $profile->tradition : null),
            'gotra' => $profile->gotra,
            'shakha_code' => $profile->shakha_code,
            'bio' => $profile->bio,
            'languages' => $profile->languages,
            'specializations' => $profile->specializations,
            'experience_years' => $profile->experience_years,
            'certification_refs' => $profile->certification_refs,
            'verification_status' => $profile->verification_status,
            'verification_documents' => $profile->verification_documents,
            'travel_buffer_minutes' => (int) ($profile->travel_buffer_minutes ?? 30),
            'travel_radius_km' => (int) ($profile->travel_radius_km ?? 25),
            'timezone' => $profile->timezone ?? 'Asia/Kolkata',
            'base_rate_minor' => $profile->base_rate_minor,
            'currency' => $profile->currency,
            'market' => $profile->market,
            'rating_avg' => (float) $profile->rating_avg,
            'rating_count' => (int) $profile->rating_count,
            'total_bookings' => (int) $profile->total_bookings,
            'status' => $profile->status,
            'deleted_at' => $profile->deleted_at?->toJSON(),
        ];
    }
}
