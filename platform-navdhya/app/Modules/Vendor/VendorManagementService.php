<?php

namespace App\Modules\Vendor;

use App\Domain\Verification\Projector\VerificationProjector;
use App\Modules\Vendor\Contracts\ManagesVendors;
use App\Modules\Vendor\Models\Vendor;
use App\Platform\Api\CodedHttpException;
use App\Platform\Audit\AuditWriter;

/**
 * ManagesVendors port implementation: vendor profile writes. The
 * application gate (APPROVED partner-vendor track) runs at the HTTP
 * edge; this layer enforces the one-vendor-per-application rule and the
 * verify/suspend state machine. tenant_id comes from BelongsToTenant.
 */
final class VendorManagementService implements ManagesVendors
{
    public function __construct(
        private readonly ?AuditWriter $audit = null,
        private readonly ?VerificationProjector $projector = null,
    ) {
    }

    private function auditWriter(): AuditWriter
    {
        return $this->audit ?? app(AuditWriter::class);
    }

    private function projector(): VerificationProjector
    {
        return $this->projector ?? app(VerificationProjector::class);
    }
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
        return (new Vendor())->getConnection()->transaction(function () use ($vendorId): array {
            $vendor = Vendor::query()->findOrFail($vendorId);

            if ($vendor->verification_status === Vendor::VERIFICATION_VERIFIED) {
                throw new CodedHttpException(409, 'state_forbidden', 'Vendor is already verified.');
            }

            $before = ['verification_status' => $vendor->verification_status];

            $actorId = (string) (app(\App\Platform\Tenancy\TenantContext::class)->membershipId() ?? 'system:admin');
            $claim = $this->projector()->grantManualClaimAndProject(
                subject: $vendor,
                subjectType: 'vendor',
                actorId: $actorId,
                metadata: ['source' => 'vendor.admin.verify'],
            );

            $this->auditWriter()->record(
                'vendor.profile.verified',
                ['module' => 'vendor', 'resource_type' => 'vendor', 'resource_id' => (string) $vendor->getKey()],
                $before,
                ['verification_status' => $vendor->verification_status, 'claim_id' => (string) $claim->getKey()],
            );

            return Vendor::shape($vendor);
        });
    }

    public function suspend(string $vendorId): array
    {
        return (new Vendor())->getConnection()->transaction(function () use ($vendorId): array {
            $vendor = Vendor::query()->findOrFail($vendorId);

            if ($vendor->status === Vendor::STATUS_SUSPENDED) {
                throw new CodedHttpException(409, 'state_forbidden', 'Vendor is already suspended.');
            }

            $before = ['status' => $vendor->status];

            $vendor->forceFill(['status' => Vendor::STATUS_SUSPENDED])->save();

            $this->auditWriter()->record(
                'vendor.profile.suspended',
                ['module' => 'vendor', 'resource_type' => 'vendor', 'resource_id' => (string) $vendor->getKey()],
                $before,
                ['status' => $vendor->status],
            );

            return Vendor::shape($vendor);
        });
    }
}
