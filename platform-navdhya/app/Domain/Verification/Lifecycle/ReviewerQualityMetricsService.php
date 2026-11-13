<?php

namespace App\Domain\Verification\Lifecycle;

use App\Domain\Verification\Models\OnboardingAppeal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reviewer quality & rubber-stamping detection telemetry (§12.2 / T-TR07-08).
 */
final class ReviewerQualityMetricsService
{
    /**
     * @return array{
     *   reviewer_id: string,
     *   total_decisions: int,
     *   decisions_with_prior_doc_access: int,
     *   decisions_without_prior_document_access: int,
     *   rubber_stamp_alert: bool,
     *   appeals_decided_against: int,
     *   appeals_overturned: int,
     *   overturn_rate: float
     * }
     */
    public function metricsForReviewer(string $tenantId, string $reviewerId, int $windowDays = 30): array
    {
        $since = Carbon::now('UTC')->subDays($windowDays);

        $decisionLogs = DB::connection('tenant_shared')
            ->table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->whereIn('action', [
                'onboarding.application.approved',
                'onboarding.application.rejected',
                'onboarding.decision.initiated',
                'onboarding.decision.countersigned',
            ])
            ->where('occurred_at', '>=', $since)
            ->get();

        $docAccessLogs = DB::connection('tenant_shared')
            ->table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('action', 'onboarding.document.accessed')
            ->where('occurred_at', '>=', $since)
            ->get();

        $reviewerDecisions = $decisionLogs->filter(static function ($row) use ($reviewerId): bool {
            $actor = json_decode((string) $row->actor, true);

            return (string) ($actor['id'] ?? $actor['user_id'] ?? '') === $reviewerId;
        });

        $reviewerDocAccessTimes = $docAccessLogs
            ->filter(static function ($row) use ($reviewerId): bool {
                $actor = json_decode((string) $row->actor, true);

                return (string) ($actor['id'] ?? $actor['user_id'] ?? '') === $reviewerId;
            })
            ->map(static fn($row): string => (string) $row->occurred_at)
            ->values()
            ->all();

        $withAccess = 0;
        $withoutAccess = 0;

        foreach ($reviewerDecisions as $decision) {
            $decisionTime = (string) $decision->occurred_at;
            $hasPrior = false;
            foreach ($reviewerDocAccessTimes as $accessTime) {
                if ($accessTime <= $decisionTime) {
                    $hasPrior = true;
                    break;
                }
            }
            if ($hasPrior) {
                $withAccess++;
            } else {
                $withoutAccess++;
            }
        }

        $totalDecisions = $reviewerDecisions->count();

        $appeals = OnboardingAppeal::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($reviewerId): void {
                $q->where('original_actor_id', $reviewerId)
                    ->orWhere('original_countersign_actor_id', $reviewerId);
            })
            ->whereNotNull('decided_at')
            ->get();

        $overturned = $appeals->where('outcome', 'overturned')->count();
        $totalAppeals = $appeals->count();

        return [
            'reviewer_id' => $reviewerId,
            'total_decisions' => $totalDecisions,
            'decisions_with_prior_doc_access' => $withAccess,
            'decisions_without_prior_document_access' => $withoutAccess,
            'rubber_stamp_alert' => $withoutAccess > 0,
            'appeals_decided_against' => $totalAppeals,
            'appeals_overturned' => $overturned,
            'overturn_rate' => $totalAppeals > 0 ? round($overturned / $totalAppeals, 4) : 0.0,
        ];
    }
}
