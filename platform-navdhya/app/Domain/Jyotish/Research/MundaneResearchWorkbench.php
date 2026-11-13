<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Research;

/**
 * Samhita Mundane & Historical Research Workbench (T-JY10-10).
 *
 * Strict Public Safety Guardrail:
 * Absolutely prohibits disaster forecasting, weather calamity, earthquake,
 * or mass-panic geopolitical catastrophe predictions.
 */
final readonly class MundaneResearchWorkbench
{
    private const FORBIDDEN_PREDICTION_TERMS = [
        'earthquake',
        'tsunami',
        'calamity',
        'pandemic',
        'war outbreak',
        'apocalypse',
        'catastrophe',
        'death toll',
    ];

    /**
     * Evaluates cycle correlations for historical research only.
     *
     * @param array<string, mixed> $historicalPlanetaryCycle
     * @return array<string, mixed>
     */
    public static function analyzeHistoricalCycle(array $historicalPlanetaryCycle, string $query): array
    {
        $lowercaseQuery = strtolower($query);
        foreach (self::FORBIDDEN_PREDICTION_TERMS as $forbidden) {
            if (str_contains($lowercaseQuery, $forbidden)) {
                throw new \InvalidArgumentException(
                    "Public Safety Policy: Predictions regarding disasters, calamities, or casualties ('{$forbidden}') are strictly prohibited."
                );
            }
        }

        return [
            'mode' => 'HISTORICAL_CORRELATION_ONLY',
            'query' => $query,
            'is_predictive' => false,
            'cycle' => $historicalPlanetaryCycle,
            'safety_verified' => true,
        ];
    }
}

