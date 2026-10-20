<?php

namespace App\Modules\Vendor\Contracts;

/**
 * Vendor profile write port (deptrac-blessed Contracts surface): the
 * partner self-serve and admin surfaces manage vendor profiles through
 * this port without exposing models. Ambient tenant context scopes every
 * call. State conflicts surface as kernel CodedHttpExceptions (409) so
 * the wire code matches the envelope registry exactly (doc 06 §4.2).
 *
 * @phpstan-type VendorProfile array{id: string, object: string, application_id: string, owner_user_id: int, name: string, description: string|null, city: string|null, state: string|null, status: string, verification_status: string, created_at: string, updated_at: string}
 */
interface ManagesVendors
{
    /**
     * Create a vendor bound to the applicant's APPROVED partner-vendor
     * application (the application gate runs at the HTTP edge); starts
     * ACTIVE with verification PENDING.
     *
     * @param  string  $identityId  platform user id of the owner
     * @param  array{name: string, description?: string|null, city?: string|null, state?: string|null}  $data
     * @return VendorProfile
     *
     * @throws \App\Platform\Api\CodedHttpException 409 conflict when the application already has a vendor
     */
    public function create(string $identityId, string $applicationId, array $data): array;

    /**
     * Partial update of the mutable profile fields (status transitions
     * go through verify/suspend, never through update).
     *
     * @param  array{name?: string, description?: string|null, city?: string|null, state?: string|null}  $data
     * @return VendorProfile
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the vendor is absent or out of tenant scope
     */
    public function update(string $vendorId, array $data): array;

    /**
     * Mark the vendor profile verified (admin verb).
     *
     * @return VendorProfile
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the vendor is absent or out of tenant scope
     * @throws \App\Platform\Api\CodedHttpException 409 state_forbidden when already verified
     */
    public function verify(string $vendorId): array;

    /**
     * Suspend the vendor profile (admin verb).
     *
     * @return VendorProfile
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the vendor is absent or out of tenant scope
     * @throws \App\Platform\Api\CodedHttpException 409 state_forbidden when already suspended
     */
    public function suspend(string $vendorId): array;
}
