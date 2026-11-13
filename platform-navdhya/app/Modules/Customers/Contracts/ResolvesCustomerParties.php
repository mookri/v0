<?php

declare(strict_types=1);

namespace App\Modules\Customers\Contracts;

/**
 * Verifies whether an acting user is an authorized party to a booking's
 * account holder (either account owner or registered CustomerFamilyMember)
 * for Feedback submission eligibility (TS-08 / T-TS02-02).
 */
interface ResolvesCustomerParties
{
    public function isAuthorizedPartyForOwner(int $actingUserId, int $ownerUserId): bool;
}

