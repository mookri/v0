<?php

namespace App\Domain\Jyotish\Rules;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Rules\Datasets\ParashariDignities;
use App\Domain\Jyotish\Support\Angle;

/**
 * Planetary Dignity Engine (T-JY04-01).
 *
 * Evaluates:
 * - Exaltation (Uchcha), Debilitation (Neecha), Moolatrikona, Swakshetra (Own sign)
 * - 5-fold compound relationship (Panchadha Sambandha: Natural + Temporal)
 * - Quantitative Uchcha Bala / Exaltation ratio [0.0, 1.0]
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class DignityEngine implements ZodiacConstants
{
    /**
     * Evaluate planetary dignity and relationship.
     *
     * @param string $planet Planetary name ('sun', 'moon', etc.)
     * @param float $siderealDegrees Sidereal longitude in degrees [0, 360)
     * @param array<string, int> $allPlanetaryRashis Map of planet name => rashi index (0..11) for temporal calculation
     * @return array{
     *     planet: string,
     *     rashi_index: int,
     *     rashi_name: string,
     *     degrees_in_rashi: float,
     *     sign_lord: string,
     *     dignity: string,
     *     natural_relation: string,
     *     temporal_relation: string,
     *     compound_relation: string,
     *     exaltation_score: float
     * }
     */
    public static function evaluate(
        string $planet,
        float $siderealDegrees,
        array $allPlanetaryRashis = []
    ): array {
        $p = strtolower(trim($planet));
        $normalizedDeg = Angle::normalize($siderealDegrees);
        $rashiIndex = (int) floor($normalizedDeg / self::RASHI_SPAN_DEGREES);
        if ($rashiIndex >= 12) {
            $rashiIndex = 11;
        }
        $degInRashi = $normalizedDeg - ($rashiIndex * self::RASHI_SPAN_DEGREES);
        $rashiName = self::RASHIS[$rashiIndex]['name'];
        $signLord = ParashariDignities::RASHI_LORDS[$rashiIndex];

        // 1. Exaltation & Debilitation Check
        $exaltMeta = ParashariDignities::EXALTATIONS[$p] ?? null;
        $debiltMeta = ParashariDignities::DEBILITATIONS[$p] ?? null;

        $isExalted = ($exaltMeta !== null && $exaltMeta['rashi'] === $rashiIndex);
        $isDebilitated = ($debiltMeta !== null && $debiltMeta['rashi'] === $rashiIndex);

        // 2. Moolatrikona Check
        $moolaMeta = ParashariDignities::MOOLATRIKONA[$p] ?? null;
        $isMoolatrikona = false;
        if ($moolaMeta !== null && $moolaMeta['rashi'] === $rashiIndex) {
            if ($degInRashi >= $moolaMeta['min'] && $degInRashi < $moolaMeta['max']) {
                $isMoolatrikona = true;
            }
        }

        // 3. Own Sign Check (Swakshetra)
        $ownSigns = ParashariDignities::OWN_SIGNS[$p] ?? [];
        $isOwnSign = in_array($rashiIndex, $ownSigns, true);

        // 4. Natural Relationship with Sign Lord
        $naturalRel = 'neutral';
        if ($signLord === $p) {
            $naturalRel = 'self';
        } else {
            $pRelations = ParashariDignities::NATURAL_RELATIONSHIPS[$p] ?? null;
            if ($pRelations) {
                if (in_array($signLord, $pRelations['friends'], true)) {
                    $naturalRel = 'friend';
                } elseif (in_array($signLord, $pRelations['enemies'], true)) {
                    $naturalRel = 'enemy';
                }
            }
        }

        // 5. Temporal Relationship (Tatkalika Sambandha)
        $temporalRel = 'none';
        if ($signLord !== $p && isset($allPlanetaryRashis[$signLord])) {
            $lordRashi = $allPlanetaryRashis[$signLord];
            $rashiDist = (($lordRashi - $rashiIndex + 12) % 12) + 1; // 1-indexed house count from planet to lord

            // Lord in 2, 3, 4, 10, 11, 12 from planet is a temporal friend
            if (in_array($rashiDist, [2, 3, 4, 10, 11, 12], true)) {
                $temporalRel = 'friend';
            } else {
                $temporalRel = 'enemy';
            }
        }

        // 6. Compound 5-fold Relationship (Panchadha Sambandha)
        $compoundRel = self::computeCompoundRelationship($naturalRel, $temporalRel);

        // 7. Overall Dignity Category
        $dignity = match (true) {
            $isExalted => 'exalted',
            $isDebilitated => 'debilitated',
            $isMoolatrikona => 'moolatrikona',
            $isOwnSign => 'own_sign',
            $compoundRel === 'adhi_mitra' => 'great_friend',
            $compoundRel === 'mitra' => 'friend',
            $compoundRel === 'sama' => 'neutral',
            $compoundRel === 'shatru' => 'enemy',
            $compoundRel === 'adhi_shatru' => 'bitter_enemy',
            default => 'neutral',
        };

        // 8. Quantitative Uchcha Bala Exaltation Score [0.0, 1.0]
        $exaltScore = self::calculateExaltationScore($p, $normalizedDeg);

        return [
            'planet' => $p,
            'rashi_index' => $rashiIndex,
            'rashi_name' => $rashiName,
            'degrees_in_rashi' => $degInRashi,
            'sign_lord' => $signLord,
            'dignity' => $dignity,
            'natural_relation' => $naturalRel,
            'temporal_relation' => $temporalRel,
            'compound_relation' => $compoundRel,
            'exaltation_score' => $exaltScore,
        ];
    }

    /**
     * Compute Panchadha Sambandha compound relationship.
     */
    private static function computeCompoundRelationship(string $natural, string $temporal): string
    {
        if ($natural === 'self') {
            return 'self';
        }

        if ($temporal === 'none') {
            return match ($natural) {
                'friend' => 'mitra',
                'enemy' => 'shatru',
                default => 'sama',
            };
        }

        return match ([$natural, $temporal]) {
            ['friend', 'friend'] => 'adhi_mitra',
            ['friend', 'enemy'], ['enemy', 'friend'], ['neutral', 'neutral'] => 'sama',
            ['neutral', 'friend'] => 'mitra',
            ['neutral', 'enemy'] => 'shatru',
            ['enemy', 'enemy'] => 'adhi_shatru',
            default => 'sama',
        };
    }

    /**
     * Calculate continuous Uchcha score where deep exaltation = 1.0, deep debilitation = 0.0.
     */
    private static function calculateExaltationScore(string $planet, float $normalizedDegrees): float
    {
        $debiltMeta = ParashariDignities::DEBILITATIONS[$planet] ?? null;
        if ($debiltMeta === null) {
            return 0.5;
        }

        $deepDebilitationDeg = ($debiltMeta['rashi'] * self::RASHI_SPAN_DEGREES) + $debiltMeta['degree'];
        $diff = abs($normalizedDegrees - $deepDebilitationDeg);
        if ($diff > 180.0) {
            $diff = 360.0 - $diff;
        }

        // diff is 0 at debilitation -> score 0.0; diff is 180 at exaltation -> score 1.0
        return round($diff / 180.0, 6);
    }
}

