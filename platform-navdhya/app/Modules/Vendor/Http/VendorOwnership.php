<?php

namespace App\Modules\Vendor\Http;

use App\Modules\Vendor\Models\Vendor;
use App\Platform\Api\CodedHttpException;
use Illuminate\Http\Request;

/**
 * Partner-surface access resolution shared by the vendor controllers:
 * 404 when the vendor is absent or in another tenant (TenantScope keeps
 * cross-tenant rows invisible — no existence leakage), 403 when a
 * same-tenant vendor belongs to someone else, 401 when no identity is
 * attached. Admin routes bypass this trait — capability + step-up gates
 * govern them instead.
 */
trait VendorOwnership
{
    /**
     * @return array<string, mixed> the vendor's wire shape (Vendor::shape)
     */
    protected function ownedVendor(Request $request, string $vendorId): array
    {
        $vendor = Vendor::query()->find($vendorId);

        if ($vendor === null) {
            throw new CodedHttpException(404, 'not_found', 'Vendor not found.');
        }

        $identityId = $this->identityId($request);

        if ((string) $vendor->owner_user_id !== $identityId) {
            throw new CodedHttpException(403, 'permission_missing', 'You do not own this vendor.');
        }

        return Vendor::shape($vendor);
    }

    /**
     * Platform user id of the authenticated human (fail-closed 401).
     */
    protected function identityId(Request $request): string
    {
        $identityId = $request->user()?->getAuthIdentifier();

        if ($identityId === null || $identityId === '') {
            throw new CodedHttpException(401, 'unauthenticated', 'Sign in to continue.');
        }

        return (string) $identityId;
    }
}
