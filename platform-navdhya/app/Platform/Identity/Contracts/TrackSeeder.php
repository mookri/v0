<?php

namespace App\Platform\Identity\Contracts;

/**
 * Track seeding on invite acceptance (kernel-owned contract, module
 * implementations). The identity layer knows an invite carries a track;
 * what that track materializes (draft application, customer record,
 * nothing) is the owning module's decision. Bound by module providers;
 * absent binding means no seeding (fail-open for tracks, never for auth).
 */
interface TrackSeeder
{
    /**
     * @return array<string, mixed>|null Seeded reference (e.g. application
     *   id + track version) or null when the track seeds nothing.
     */
    public function seed(string $track, string $identityId): ?array;
}
