<?php

namespace App\Modules\Onboarding;

use App\Modules\Onboarding\Contracts\ManagesPartnerApplications;
use App\Modules\Onboarding\Models\PartnerApplication;

/**
 * ManagesPartnerApplications port implementation: track version pinning
 * comes from the TrackCatalog (single source of truth) and every row stays
 * tenant-scoped by the model's tenant scope, ambient-tenant scoped.
 */
final class PartnerApplicationService implements ManagesPartnerApplications
{
    /**
     * @return array{id: string, object: string, track: string, track_version: string, status: string}
     */
    public function start(string $identityId, string $track): array
    {
        $definition = TrackCatalog::tracks()[$track] ?? null;

        if ($definition === null) {
            throw new \InvalidArgumentException("Unknown track '{$track}'.");
        }

        $application = new PartnerApplication();
        $application->forceFill([
            'identity_id' => $identityId,
            'track' => $track,
            'track_version' => $definition['version'],
            'status' => PartnerApplication::STATUS_DRAFT,
        ]);
        $application->save();

        return $this->summary($application);
    }

    /**
     * @return array{id: string, object: string, track: string, track_version: string, status: string, submitted_at: string|null, reviewed_at: string|null, rejection_code: string|null, evidence: array<string, mixed>|null}|null
     */
    public function latestForIdentity(string $identityId, string $track): ?array
    {
        $application = PartnerApplication::query()
            ->where('identity_id', $identityId)
            ->where('track', $track)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($application === null) {
            return null;
        }

        return $this->summary($application) + [
            'submitted_at' => $application->submitted_at?->toJSON(),
            'reviewed_at' => $application->reviewed_at?->toJSON(),
            'rejection_code' => $application->rejection_code,
            'evidence' => $application->evidence,
        ];
    }

    /**
     * @return array{id: string, object: string, track: string, track_version: string, status: string}
     */
    private function summary(PartnerApplication $application): array
    {
        return [
            'id' => (string) $application->getKey(),
            'object' => 'partner_application',
            'track' => $application->track,
            'track_version' => $application->track_version,
            'status' => $application->status,
        ];
    }
}
