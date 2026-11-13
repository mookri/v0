<?php

declare(strict_types=1);

namespace App\Modules\Customers\Services;

use App\Modules\Customers\Contracts\ResolvesCustomerParties;
use App\Modules\Customers\Models\CustomerFamilyMember;

final class CustomerPartyResolver implements ResolvesCustomerParties
{
    public function isAuthorizedPartyForOwner(int $actingUserId, int $ownerUserId): bool
    {
        if ($actingUserId === $ownerUserId) {
            return true;
        }

        // Check whether an authorized family member record links actingUserId under the owner.
        return CustomerFamilyMember::query()
            ->where('user_id', $ownerUserId)
            ->where(function ($q) use ($actingUserId): void {
                $q->where('id', (string) $actingUserId)
                    ->orWhere('consent_ref', 'delegated_user:' . $actingUserId);
            })
            ->exists();
    }
}

