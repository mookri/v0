<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain;

use App\Modules\Feedback\Contracts\ReadsFeedbackQuality;
use App\Modules\Incident\Models\IncidentInterimRestriction;
use App\Modules\Incident\Models\IncidentReplacement;
use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use Carbon\CarbonImmutable;

/**
 * Hard Eligibility Gate & Honest Ranking Engine for Replacement Recovery (TS-13 / ADR-0065).
 *
 * Guarantees:
 *  - Every gate is recorded explicitly in `eligibility_gates_passed[]`
 *  - Cross-tradition fallback matches are flagged `PARTIAL_GATES` (never silently equivalent)
 *  - Candidates holding active safety restrictions or concurrent standby holds are excluded
 */
final class ReplacementEligibilityEvaluator
{
    public function __construct(
        private readonly ListsPanditProfiles $profiles,
        private readonly ManagesPanditAssignments $assignments,
        private readonly ?ReadsFeedbackQuality $quality = null,
    ) {
    }

    /**
     * @return list<array{
     *   pandit_id: string,
     *   display_name: string,
     *   score: float,
     *   eligibility_gates_passed: list<string>,
     *   gate_disclosure_status: string,
     *   tradition: string|null
     * }>
     */
    public function rankEligibleCandidates(
        string $tenantId,
        string $serviceSku,
        string $startIso,
        string $endIso,
        ?string $originalPanditId = null,
        ?string $preferredTradition = null,
        ?string $preferredLanguage = null,
    ): array {
        $now = CarbonImmutable::now('UTC')->toDateTimeString();
        $list = $this->profiles->publicProfiles([], 50)['items'];

        $restrictedPanditIds = IncidentInterimRestriction::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', 'pandit')
            ->whereNull('lifted_at')
            ->where('expires_at', '>', $now)
            ->pluck('subject_id')
            ->all();

        $heldPanditIds = IncidentReplacement::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'RESERVED_HOLD')
            ->where('offer_expires_at', '>', $now)
            ->pluck('candidate_pandit_id')
            ->filter()
            ->all();

        $candidates = [];

        foreach ($list as $item) {
            $candidateId = (string) $item['id'];
            if ($originalPanditId !== null && $candidateId === $originalPanditId) {
                continue;
            }
            if (in_array($candidateId, $restrictedPanditIds, true)) {
                continue;
            }
            if (in_array($candidateId, $heldPanditIds, true)) {
                continue;
            }

            $detail = $this->profiles->profileById($candidateId);
            if ($detail === null || $detail['status'] !== 'ACTIVE' || $detail['verification_status'] !== 'VERIFIED') {
                continue;
            }

            $gates = ['active_verified', 'no_active_safety_restriction', 'not_held_by_concurrent_offer'];

            if ($serviceSku !== '' && ! $this->profiles->isQualifiedForService($tenantId, $candidateId, $serviceSku)) {
                continue;
            }
            $gates[] = 'qualified_for_service';

            if ($this->assignments->hasWindowConflict($candidateId, $startIso, $endIso)) {
                continue;
            }
            $gates[] = 'no_schedule_conflict';

            $rawTradition = $detail['tradition'] ?? null;
            $candidateTradition = $rawTradition instanceof \BackedEnum
                ? (string) $rawTradition->value
                : ($rawTradition !== null ? (string) $rawTradition : null);
            $traditionMatches = $preferredTradition === null
                || $preferredTradition === ''
                || ($candidateTradition !== null && strcasecmp($candidateTradition, $preferredTradition) === 0);

            if ($traditionMatches) {
                $gates[] = 'tradition_match';
            }

            $languages = array_map('strtolower', (array) ($detail['languages'] ?? []));
            $languageMatches = $preferredLanguage === null
                || $preferredLanguage === ''
                || in_array(strtolower($preferredLanguage), $languages, true);
            if ($languageMatches) {
                $gates[] = 'language_match';
            }

            $quality = $this->quality?->summaryForSubject('pandit', $candidateId);
            $bayesian = (float) ($quality['bayesian_average'] ?? $detail['rating_avg'] ?? 4.2);
            $wilsonPunctual = (float) ($quality['punctuality_wilson_lower'] ?? 0.5);

            // Composite score bounded to [0, 100]
            $score = round(
                ($traditionMatches ? 35.0 : 15.0)
                + ($languageMatches ? 15.0 : 5.0)
                + (min(5.0, max(0.0, $bayesian)) * 6.0)
                + (min(1.0, max(0.0, $wilsonPunctual)) * 20.0),
                2
            );

            $candidates[] = [
                'pandit_id' => $candidateId,
                'display_name' => (string) $item['display_name'],
                'score' => $score,
                'eligibility_gates_passed' => $gates,
                'gate_disclosure_status' => $traditionMatches ? 'FULL_GATES' : 'PARTIAL_GATES',
                'tradition' => $candidateTradition,
            ];
        }

        usort($candidates, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $candidates;
    }
}
