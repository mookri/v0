<?php

namespace App\Modules\Onboarding\Contracts;

/**
 * Partner application port (deptrac-blessed Contracts surface): modules
 * that own a track's applicant-facing surface (the Pandit module owns the
 * pandit track) start and read applications without importing onboarding
 * models or controllers. Ambient tenant context scopes every call.
 */
interface ManagesPartnerApplications
{
    /**
     * Start a new application pinned to the catalog's current track
     * version (stage 1 intake). Unknown tracks fail closed.
     *
     * @param  string  $identityId  platform user id of the applicant
     * @return array{id: string, object: string, track: string, track_version: string, status: string}
     *
     * @throws \InvalidArgumentException when the track is unknown
     */
    public function start(string $identityId, string $track): array;

    /**
     * Latest application for the identity on the track (any state), or
     * null when the identity never applied to it.
     *
     * @return array{id: string, object: string, track: string, track_version: string, status: string, submitted_at: string|null, reviewed_at: string|null, rejection_code: string|null, evidence: array<string, mixed>|null}|null
     */
    public function latestForIdentity(string $identityId, string $track): ?array;
}
