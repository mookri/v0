<?php

namespace App\Domain\Privacy\Incidents;

use App\Domain\Privacy\Models\PrivacyIncident;
use App\Platform\Audit\AuditWriter;

final class IncidentRecorder
{
    public function __construct(private readonly AuditWriter $audit)
    {
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        ?string $tenantId,
        string $severity,
        string $category,
        string $summary,
        int $affectedSubjectsCount = 0,
        ?string $reportedBy = null,
        array $metadata = [],
    ): PrivacyIncident {
        $incident = new PrivacyIncident();
        $incident->forceFill([
            'tenant_id' => $tenantId,
            'severity' => $severity,
            'category' => $category,
            'summary' => $summary,
            'affected_subjects_count' => $affectedSubjectsCount,
            'detected_at' => now()->utc(),
            'reported_by' => $reportedBy,
            'metadata' => $metadata,
        ])->save();

        if ($tenantId !== null && $tenantId !== '') {
            $this->audit->record(
                'privacy.incident.recorded',
                ['module' => 'privacy', 'resource_type' => 'privacy_incident', 'resource_id' => (string) $incident->getKey()],
                null,
                ['severity' => $severity, 'category' => $category, 'affected_subjects_count' => $affectedSubjectsCount],
            );
        }

        return $incident;
    }
}

