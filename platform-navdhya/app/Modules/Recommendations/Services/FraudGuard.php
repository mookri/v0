<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Recommendations\Models\RecoImpression;
use App\Modules\Recommendations\Models\RecoInteraction;
use Illuminate\Support\Collection;

class FraudGuard
{
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_UNVERIFIED_EXPOSURE = 'unverified_exposure';
    public const STATUS_SUSPECT = 'suspect';
    public const STATUS_RATE_LIMITED = 'rate_limited';

    public const MAX_PER_SESSION_INTERACTIONS = 50;
    public const MIN_HUMAN_DWELL_MS = 50;

    /**
     * Validate an interaction candidate for genuine human exposure and dwell integrity.
     *
     * @param array<string, mixed> $act
     * @return array{valid: bool, status: string, reason: string|null}
     */
    public function validateInteraction(array $act): array
    {
        $entityId = (string) ($act['entity_id'] ?? '');
        $impressionId = isset($act['impression_id']) ? (string) $act['impression_id'] : null;
        $sessionId = isset($act['session_id']) ? (string) $act['session_id'] : null;
        $dwellMs = (int) ($act['dwell_ms'] ?? 0);

        // 1. Exposure verification: Click must have a pre-existing impression in the session
        $hasImpression = false;
        if (! empty($impressionId)) {
            $hasImpression = RecoImpression::where('id', $impressionId)
                ->where('entity_id', $entityId)
                ->exists();
        } elseif (! empty($sessionId)) {
            $hasImpression = RecoImpression::where('session_id', $sessionId)
                ->where('entity_id', $entityId)
                ->exists();
        }

        if (! $hasImpression) {
            return [
                'valid' => false,
                'status' => self::STATUS_UNVERIFIED_EXPOSURE,
                'reason' => 'No pre-existing impression record found for this session/entity.',
            ];
        }

        // 2. Minimum dwell threshold check
        if ($dwellMs > 0 && $dwellMs < self::MIN_HUMAN_DWELL_MS) {
            return [
                'valid' => false,
                'status' => self::STATUS_SUSPECT,
                'reason' => 'Dwell time below human physical threshold.',
            ];
        }

        // 3. Per-session interaction volume cap
        if (! empty($sessionId)) {
            $sessionCount = RecoInteraction::where('session_id', $sessionId)->count();
            if ($sessionCount >= self::MAX_PER_SESSION_INTERACTIONS) {
                return [
                    'valid' => false,
                    'status' => self::STATUS_RATE_LIMITED,
                    'reason' => 'Maximum allowed interactions per session exceeded.',
                ];
            }
        }

        return [
            'valid' => true,
            'status' => self::STATUS_VERIFIED,
            'reason' => null,
        ];
    }

    /**
     * Filter a collection of interactions to only legitimate, verified records.
     *
     * @param Collection<int, RecoInteraction> $interactions
     * @return Collection<int, RecoInteraction>
     */
    public function filterLegitimate(Collection $interactions): Collection
    {
        return $interactions->filter(fn (RecoInteraction $i) => $i->status === self::STATUS_VERIFIED);
    }
}

