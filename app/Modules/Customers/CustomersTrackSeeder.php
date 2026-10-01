<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Onboarding\OnboardingTrackSeeder;
use App\Platform\Identity\Contracts\TrackSeeder;

/**
 * Customer-track seeding on invite acceptance: the `customer` track
 * materializes a buyer profile for the invited user (idempotent — repeat
 * acceptance reuses the row). Every other track delegates to the
 * onboarding seeder; this binding wins because the customers module boots
 * after onboarding (priority 150 > 100, last bind wins).
 */
final class CustomersTrackSeeder implements TrackSeeder
{
    public function __construct(private readonly OnboardingTrackSeeder $supply)
    {
    }

    public function seed(string $track, string $identityId): ?array
    {
        if ($track !== 'customer') {
            return $this->supply->seed($track, $identityId);
        }

        $profile = BuyerProfile::query()->firstOrCreate(
            ['user_id' => $identityId],
            ['status' => BuyerProfile::STATUS_ACTIVE]
        );

        return ['buyer_profile_id' => (string) $profile->getKey()];
    }
}
