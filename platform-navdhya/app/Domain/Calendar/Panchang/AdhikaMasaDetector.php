<?php

namespace App\Domain\Calendar\Panchang;

use Carbon\CarbonImmutable;

/**
 * Adhika (intercalary) and Kshaya (expunged) lunar month detector.
 *
 * Rules:
 * - A lunar month with ZERO solar sankrantis is an ADHIKA (intercalary) masa.
 * - A lunar month with TWO solar sankrantis is a KSHAYA (expunged) masa.
 * - A lunar month with ONE solar sankranti is a NIJA (normal) masa.
 */
final class AdhikaMasaDetector
{
    public const TYPE_NIJA = 'NIJA';
    public const TYPE_ADHIKA = 'ADHIKA';
    public const TYPE_KSHAYA = 'KSHAYA';

    /**
     * Determine month status based on the number of solar ingresses during the lunar month interval.
     *
     * @param int $sankrantiCount Number of solar ingresses in [month_start, month_end]
     * @return array{type: string, is_adhika: bool, is_kshaya: bool}
     */
    public static function classifyByCount(int $sankrantiCount): array
    {
        $type = match (true) {
            $sankrantiCount === 0 => self::TYPE_ADHIKA,
            $sankrantiCount >= 2 => self::TYPE_KSHAYA,
            default => self::TYPE_NIJA,
        };

        return [
            'type' => $type,
            'is_adhika' => $type === self::TYPE_ADHIKA,
            'is_kshaya' => $type === self::TYPE_KSHAYA,
        ];
    }

    /**
     * Inspect solar position at month boundaries and check for ingresses.
     *
     * @param CarbonImmutable $lunarMonthStart
     * @param CarbonImmutable $lunarMonthEnd
     * @return array{type: string, is_adhika: bool, is_kshaya: bool, sankranti_count: int}
     */
    public static function detect(CarbonImmutable $lunarMonthStart, CarbonImmutable $lunarMonthEnd): array
    {
        // Sample solar rashis across the lunar month
        $startLon = SankrantiCalculator::siderealSunAtTimestamp((float) $lunarMonthStart->getTimestamp());
        $endLon = SankrantiCalculator::siderealSunAtTimestamp((float) $lunarMonthEnd->getTimestamp());

        $startRashi = (int) floor($startLon / 30.0) + 1;
        $endRashi = (int) floor($endLon / 30.0) + 1;

        $rashiDelta = ($endRashi - $startRashi + 12) % 12;

        $classification = self::classifyByCount($rashiDelta);

        return array_merge($classification, [
            'sankranti_count' => $rashiDelta,
            'start_rashi' => $startRashi,
            'end_rashi' => $endRashi,
        ]);
    }
}

