<?php

namespace App\Modules\Catalog\Domain\Duration;

use App\Modules\Catalog\Contracts\DurationResolutionResult;

/**
 * Pure Domain Duration Rules Engine (F2).
 * Strictly astro-neutral, deterministic, and free of database dependencies.
 */
final class DurationRules
{
    /**
     * @param int $baseMinutes
     * @param array<int, array{scope: string, scope_key: string, rule: array<string, mixed>}> $rules
     * @param int $participants
     * @param string|null $packageTier
     * @param string|null $tradition
     * @param \DateTimeInterface|null $at
     */
    public static function resolve(
        int $baseMinutes,
        array $rules,
        int $participants = 1,
        ?string $packageTier = null,
        ?string $tradition = null,
        ?\DateTimeInterface $at = null,
        bool $isAuthoritative = true
    ): DurationResolutionResult {
        $minutes = max(15, $baseMinutes);
        $minBound = 15;
        $maxBound = 720; // 12 hours cap
        $evaluatedRules = [];

        // 1. Process BASE scope rule if present
        foreach ($rules as $ruleEntry) {
            if ($ruleEntry['scope'] === 'BASE') {
                $r = $ruleEntry['rule'];
                if (isset($r['unit_minutes'])) {
                    $minutes = (int) $r['unit_minutes'];
                }
                if (isset($r['min_bound'])) {
                    $minBound = max($minBound, (int) $r['min_bound']);
                }
                if (isset($r['max_bound'])) {
                    $maxBound = min($maxBound, (int) $r['max_bound']);
                }
                $evaluatedRules['BASE'] = $ruleEntry;
                break;
            }
        }

        // 2. Process PACKAGE_TIER scope rule
        if ($packageTier !== null) {
            foreach ($rules as $ruleEntry) {
                if ($ruleEntry['scope'] === 'PACKAGE_TIER' && strcasecmp($ruleEntry['scope_key'], $packageTier) === 0) {
                    $r = $ruleEntry['rule'];
                    if (isset($r['unit_minutes'])) {
                        $minutes = (int) $r['unit_minutes'];
                    } elseif (isset($r['multiplier']) && is_numeric($r['multiplier'])) {
                        $minutes = (int) round($minutes * (float) $r['multiplier']);
                    }
                    $add = (int) ($r['add_minutes'] ?? $r['add'] ?? 0);
                    $minutes += $add;

                    if (isset($r['min_bound'])) {
                        $minBound = max($minBound, (int) $r['min_bound']);
                    }
                    if (isset($r['max_bound'])) {
                        $maxBound = min($maxBound, (int) $r['max_bound']);
                    }
                    $evaluatedRules['PACKAGE_TIER'] = $ruleEntry;
                    break;
                }
            }
        }

        // 3. Process TRADITION scope rule
        if ($tradition !== null) {
            foreach ($rules as $ruleEntry) {
                if ($ruleEntry['scope'] === 'TRADITION' && strcasecmp($ruleEntry['scope_key'], $tradition) === 0) {
                    $r = $ruleEntry['rule'];
                    $add = (int) ($r['add_minutes'] ?? $r['add'] ?? 0);
                    $minutes += $add;
                    if (isset($r['multiplier']) && is_numeric($r['multiplier'])) {
                        $minutes = (int) round($minutes * (float) $r['multiplier']);
                    }
                    if (isset($r['min_bound'])) {
                        $minBound = max($minBound, (int) $r['min_bound']);
                    }
                    if (isset($r['max_bound'])) {
                        $maxBound = min($maxBound, (int) $r['max_bound']);
                    }
                    $evaluatedRules['TRADITION'] = $ruleEntry;
                    break;
                }
            }
        }

        // 4. Process PER_PARTICIPANT scaling rule
        if ($participants > 1) {
            foreach ($rules as $ruleEntry) {
                if ($ruleEntry['scope'] === 'PER_PARTICIPANT') {
                    $r = $ruleEntry['rule'];
                    $per = max(1, (int) ($r['per'] ?? 1));
                    $unitMinutes = (int) ($r['unit_minutes'] ?? $r['add_minutes'] ?? $r['add'] ?? 15);
                    $extraParticipants = $participants - 1;
                    $steps = (int) floor($extraParticipants / $per);
                    $minutes += $steps * $unitMinutes;
                    if (isset($r['min_bound'])) {
                        $minBound = max($minBound, (int) $r['min_bound']);
                    }
                    if (isset($r['max_bound'])) {
                        $maxBound = min($maxBound, (int) $r['max_bound']);
                    }
                    $evaluatedRules['PER_PARTICIPANT'] = $ruleEntry;
                    break;
                }
            }
        }

        // 5. Enforce Bounds
        $clampedMinutes = max($minBound, min($maxBound, $minutes));

        // 6. Compute Deterministic Rule Snapshot Hash
        $snapshotPayload = [
            'base_minutes' => $baseMinutes,
            'participants' => $participants,
            'package_tier' => $packageTier,
            'tradition' => $tradition,
            'evaluated_rules' => $evaluatedRules,
        ];
        $snapshotHash = hash('sha256', (string) json_encode($snapshotPayload));

        return new DurationResolutionResult(
            minutes: $clampedMinutes,
            minBound: $minBound,
            maxBound: $maxBound,
            ruleSnapshotHash: $snapshotHash,
            evaluatedRules: $evaluatedRules,
            isAuthoritative: $isAuthoritative
        );
    }
}
