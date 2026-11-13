<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Recommendations\Models\RecoUserInterest;
use App\Modules\Recommendations\Models\RecoUserProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UserInterestManager
{
    public const SOURCE_EXPLICIT = 'explicit';
    public const SOURCE_INFERRED = 'inferred';

    public const SHORT_TERM_HALF_LIFE_DAYS = 2;
    public const LONG_TERM_HALF_LIFE_DAYS = 30;

    /**
     * Record or update user interest.
     * Explicit interests are never overwritten or downgraded by inferred sources.
     */
    public function recordInterest(
        int $userId,
        string $kind,
        string $key,
        float $weight = 1.0,
        string $source = self::SOURCE_INFERRED,
        ?int $halfLifeDays = null
    ): RecoUserInterest {
        $halfLife = $halfLifeDays ?? ($kind === 'session_item' ? self::SHORT_TERM_HALF_LIFE_DAYS : self::LONG_TERM_HALF_LIFE_DAYS);

        $existing = RecoUserInterest::where('user_id', $userId)
            ->where('interest_kind', $kind)
            ->where('interest_key', $key)
            ->first();

        if ($existing) {
            // Guard: Explicit source can never be overwritten by inferred source
            if ($existing->source === self::SOURCE_EXPLICIT && $source === self::SOURCE_INFERRED) {
                return $existing;
            }

            $existing->weight = $source === self::SOURCE_EXPLICIT ? max($existing->weight, $weight) : $weight;
            $existing->source = $source;
            $existing->decay_half_life_days = $halfLife;
            $existing->save();

            return $existing;
        }

        return RecoUserInterest::create([
            'user_id' => $userId,
            'interest_kind' => $kind,
            'interest_key' => $key,
            'weight' => $weight,
            'source' => $source,
            'decay_half_life_days' => $halfLife,
        ]);
    }

    /**
     * Compute effective interests with time decay and intent hierarchy.
     * Hierarchy: CurrentIntent > RecentSession > LongTerm (explicit beats inferred) > Global.
     *
     * @param int $userId
     * @param string|null $currentIntent
     * @param list<string>|null $recentSessionItems
     * @return Collection<int, array{kind: string, key: string, effective_weight: float, source: string, tier: string}>
     */
    public function getEffectiveInterests(
        int $userId,
        ?string $currentIntent = null,
        ?array $recentSessionItems = null
    ): Collection {
        $profile = RecoUserProfile::where('user_id', $userId)->first();
        if ($profile && ! $profile->personalization_enabled) {
            return collect();
        }

        $records = RecoUserInterest::where('user_id', $userId)->get();
        $now = Carbon::now();
        /** @var Collection<int, array{kind: string, key: string, effective_weight: float, source: string, tier: string}> $results */
        $results = collect();

        // 1. Current Intent Tier (Tier 1)
        if (! empty($currentIntent)) {
            $results->push([
                'kind' => 'intent',
                'key' => $currentIntent,
                'effective_weight' => 10.0,
                'source' => self::SOURCE_EXPLICIT,
                'tier' => 'current_intent',
            ]);
        }

        // 2. Recent Session Tier (Tier 2)
        if (! empty($recentSessionItems)) {
            foreach ($recentSessionItems as $idx => $itemKey) {
                $sessionWeight = 5.0 / ($idx + 1);
                $results->push([
                    'kind' => 'recent_session',
                    'key' => $itemKey,
                    'effective_weight' => (float) round($sessionWeight, 4),
                    'source' => self::SOURCE_INFERRED,
                    'tier' => 'recent_session',
                ]);
            }
        }

        // 3. Stored User Interests (Tier 3: Long term and recorded short term)
        foreach ($records as $record) {
            $updatedAt = $record->updated_at ?? $now;
            $daysPassed = max(0, $now->diffInHours($updatedAt) / 24.0);
            $halfLife = max(1, $record->decay_half_life_days ?: self::LONG_TERM_HALF_LIFE_DAYS);

            $decayFactor = pow(2, -($daysPassed / $halfLife));
            $effectiveWeight = (float) $record->weight * $decayFactor;

            // Explicit boost: explicit source gets 2x boost over inferred
            if ($record->source === self::SOURCE_EXPLICIT) {
                $effectiveWeight *= 2.0;
            }

            if ($effectiveWeight < 0.05) {
                continue;
            }

            $results->push([
                'kind' => $record->interest_kind,
                'key' => $record->interest_key,
                'effective_weight' => (float) round($effectiveWeight, 4),
                'source' => $record->source,
                'tier' => 'preference',
            ]);
        }

        return $results->sortByDesc('effective_weight')->values();
    }
}

