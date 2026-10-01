<?php

namespace App\Modules\Onboarding;

use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Identity\Contracts\TrackSeeder;

/**
 * Supply-track seeding on invite acceptance: pandit/vendor/individual
 * tracks materialize a DRAFT application pinned to the current track
 * version. Customer/staff tracks seed nothing here (customer records land
 * with the customer module; staff needs no profile row).
 */
final class OnboardingTrackSeeder implements TrackSeeder
{
    private const VERSIONS = [
        'pandit' => 'pandit-v1',
        'partner-vendor' => 'partner-vendor-v1',
        'partner-individual' => 'partner-individual-v1',
    ];

    public function seed(string $track, string $identityId): ?array
    {
        $version = self::VERSIONS[$track] ?? null;
        if ($version === null) {
            return null;
        }

        $application = new PartnerApplication();
        $application->forceFill([
            'identity_id' => $identityId,
            'track' => $track,
            'track_version' => $version,
            'status' => PartnerApplication::STATUS_DRAFT,
        ]);
        $application->save();

        return ['application_id' => (string) $application->getKey(), 'track_version' => $version];
    }
}
