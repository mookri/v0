<?php

namespace App\Modules\Vendor;

use App\Modules\Vendor\Contracts\ManagesVendors;
use App\Modules\Vendor\Models\Vendor;
use App\Platform\Api\CodedHttpException;

/**
 * ManagesVendors port implementation: vendor profile writes. The
 * application gate (APPROVED partner-vendor track) runs at the HTTP
 * edge; this layer enforces the one-vendor-per-application rule and the
 * verify/suspend state machine. tenant_id comes from BelongsToTenant.
 */
final class VendorManagementService implements ManagesVendors
{
    public function create(string $identityId, string $applicationId, array $data): array
    {
        if (Vendor::query()->where('application_id', $applicationId)->exists()) {
            throw new CodedHttpException(409, 'conflict', 'Application already linked to a vendor.');
        }

        $vendor = new Vendor();
        $vendor->forceFill([
            'application_id' => $applicationId,
            'owner_user_id' => (int) $identityId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'status' => Vendor::STATUS_ACTIVE,
            'verification_status' => Vendor::VERIFICATION_PENDING,
        ]);
        $vendor->save();

        return Vendor::shape($vendor);
    }

    public function update(string $vendorId, array $data): array
    {
        $vendor = Vendor::query()->findOrFail($vendorId);

        $allowed = array_intersect_key($data, array_flip(['name', 'description', 'city', 'state']));

        if ($allowed !== []) {
            $vendor->forceFill($allowed)->save();
        }

        return Vendor::shape($vendor);
    }

    public function verify(string $vendorId): array
    {
        $vendor = Vendor::query()->findOrFail($vendorId);

        if ($vendor->verification_status === Vendor::VERIFICATION_VERIFIED) {
            throw new CodedHttpException(409, 'state_forbidden', 'Vendor is already verified.');
        }

        $vendor->forceFill(['verification_status' => Vendor::VERIFICATION_VERIFIED])->save();

        return Vendor::shape($vendor);
    }

    public function suspend(string $vendorId): array
    {
        $vendor = Vendor::query()->findOrFail($vendorId);

        if ($vendor->status === Vendor::STATUS_SUSPENDED) {
            throw new CodedHttpException(409, 'state_forbidden', 'Vendor is already suspended.');
        }

        $vendor->forceFill(['status' => Vendor::STATUS_SUSPENDED])->save();

        return Vendor::shape($vendor);
    }
}
