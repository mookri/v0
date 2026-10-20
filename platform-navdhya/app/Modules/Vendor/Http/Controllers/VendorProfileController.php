<?php

namespace App\Modules\Vendor\Http\Controllers;

use App\Modules\Onboarding\Contracts\ManagesPartnerApplications;
use App\Modules\Vendor\Contracts\ManagesVendors;
use App\Modules\Vendor\Http\VendorOwnership;
use App\Modules\Vendor\Models\Vendor;
use App\Platform\Api\CursorPagination;
use App\Platform\Api\CodedHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor profile controller (partner self-serve + admin).
 *
 * Partner routes are ownership-gated (VendorOwnership trait); creation
 * additionally requires an APPROVED partner-vendor application (409
 * state_forbidden otherwise). Admin routes are capability + step-up
 * gated at the route level.
 */
final class VendorProfileController
{
    use VendorOwnership;

    public function __construct(
        private readonly ManagesVendors $vendors,
        private readonly ManagesPartnerApplications $applications,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: List owned vendors (cursor-paginated)
    public function partnerIndex(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Vendor::query()->where('owner_user_id', $this->identityId($request));
        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(static fn (Vendor $vendor): array => Vendor::shape($vendor), $items),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    // PARTNER: Create own vendor (APPROVED partner-vendor application required)
    public function partnerStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
        ]);

        $identityId = $this->identityId($request);

        $application = $this->applications->latestForIdentity($identityId, Vendor::APPLICATION_TRACK);

        if ($application === null) {
            throw new CodedHttpException(409, 'state_forbidden', 'Apply as a partner vendor first.');
        }

        if ($application['status'] !== 'APPROVED') {
            throw new CodedHttpException(409, 'state_forbidden', 'Partner vendor application is not approved yet.');
        }

        $vendor = $this->vendors->create($identityId, $application['id'], $validated);

        return new JsonResponse([
            'data' => $vendor,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    // PARTNER: Show own vendor
    public function partnerShow(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        return new JsonResponse([
            'data' => $vendor,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Update own vendor profile
    public function partnerUpdate(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $updated = $this->vendors->update($vendor['id'], $validated);

        return new JsonResponse([
            'data' => $updated,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: List all vendors (capability vendor:profile:view)
    public function adminIndex(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'max:24'],
            'verification_status' => ['sometimes', 'string', 'max:24'],
            'search' => ['sometimes', 'string', 'max:190'],
        ]);

        $query = Vendor::query();

        if (is_string($request->query('status')) && $request->query('status') !== '') {
            $query->where('status', $request->query('status'));
        }

        if (is_string($request->query('verification_status')) && $request->query('verification_status') !== '') {
            $query->where('verification_status', $request->query('verification_status'));
        }

        if (is_string($request->query('search')) && $request->query('search') !== '') {
            $query->where('name', 'like', '%'.$request->query('search').'%');
        }

        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(static fn (Vendor $vendor): array => Vendor::shape($vendor), $items),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    // ADMIN: Verify a vendor profile (step-up gated at the route)
    public function verify(Request $request, string $id): JsonResponse
    {
        $this->findVendor($id);

        $vendor = $this->vendors->verify($id);

        return new JsonResponse([
            'data' => $vendor,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: Suspend a vendor profile (step-up gated at the route)
    public function suspend(Request $request, string $id): JsonResponse
    {
        $this->findVendor($id);

        $vendor = $this->vendors->suspend($id);

        return new JsonResponse([
            'data' => $vendor,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function findVendor(string $id): Vendor
    {
        $vendor = Vendor::query()->find($id);

        if ($vendor === null) {
            throw new CodedHttpException(404, 'not_found', 'Vendor not found.');
        }

        return $vendor;
    }
}
