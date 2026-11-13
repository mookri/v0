<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Modules\Incident\Contracts\ManagesIncidents;
use App\Modules\Support\Models\ProviderImprovementPlan;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Provider Coaching & Improvement Plan Lifecycle (§18 / E-TS-08).
 *
 * Explicitly prohibited for safety cases (safety issues go to formal review,
 * never informal coaching), and strictly excludes non-provider external causes.
 */
final class ProviderImprovementPlanService
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly ?ManagesIncidents $incidents = null,
    ) {
    }

    /**
     * @param  list<string>  $bookingIds
     * @param  list<string>  $incidentRefs
     * @param  list<string>  $actions
     */
    public function propose(
        string $subjectType,
        string $subjectId,
        string $triggerPattern,
        array $incidentRefs,
        array $actions,
        array $bookingIds = [],
        bool $involvesSafetyCase = false,
    ): ProviderImprovementPlan {
        if ($involvesSafetyCase || str_contains(strtolower($triggerPattern), 'safety') || str_contains(strtolower($triggerPattern), 'harassment')) {
            throw new \DomainException('safety_pip_forbidden: Safety cases are NEVER routed to coaching or improvement plans.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $excludedExternal = 0;
        if ($this->incidents !== null && $bookingIds !== []) {
            foreach ($this->incidents->impactsForBookings($bookingIds) as $impact) {
                if (in_array($impact['attributed_cause'], ['VENUE', 'WEATHER_FORCE_MAJEURE', 'PLATFORM', 'DEVOTEE'], true)) {
                    $excludedExternal++;
                }
            }
        }

        /** @var ProviderImprovementPlan $plan */
        $plan = ProviderImprovementPlan::query()->create([
            'tenant_id' => $tenantId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'trigger_pattern' => $triggerPattern,
            'excluded_external_causes_count' => $excludedExternal,
            'evidence_incident_refs' => $incidentRefs,
            'actions' => $actions,
            'evaluation_window_end' => CarbonImmutable::now('UTC')->addDays(30)->toDateTimeString(),
            'state' => ProviderImprovementPlan::STATE_PROPOSED,
        ]);

        $this->audit->record(
            'support.improvement_plan.proposed',
            ['module' => 'support', 'resource_type' => 'provider_improvement_plan', 'resource_id' => (string) $plan->getKey()],
            null,
            ['subject_type' => $subjectType, 'subject_id' => $subjectId, 'excluded_external' => $excludedExternal]
        );

        return $plan;
    }

    public function acknowledge(string $planId, string $notes): ProviderImprovementPlan
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $plan = ProviderImprovementPlan::query()->where('tenant_id', $tenantId)->where('id', $planId)->firstOrFail();

        $plan->forceFill([
            'state' => ProviderImprovementPlan::STATE_ACKNOWLEDGED,
            'provider_acknowledged_at' => CarbonImmutable::now('UTC')->toDateTimeString(),
            'provider_notes' => $notes,
        ])->save();

        return $plan;
    }
}

