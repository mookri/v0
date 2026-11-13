<?php

namespace App\Domain\Calendar\Festivals;

use Carbon\CarbonImmutable;

/**
 * Natural language explanation builder for Vedic festival occurrence determinations.
 * Fulfills §7.5 and Gate G-FEST-1.
 */
final class ExplanationBuilder
{
    /**
     * @param string $festivalKey
     * @param string $date YYYY-MM-DD
     * @param string $placeName
     * @param string $tradition
     * @param array{starts_at: CarbonImmutable, ends_at: CarbonImmutable} $tithiWindow
     * @param array{starts_at: CarbonImmutable, ends_at: CarbonImmutable} $anchorWindow
     * @param ?string $alternateDateRejection
     * @return array{
     *     festival: string,
     *     date: string,
     *     place: string,
     *     tradition: string,
     *     scripture_reference: string,
     *     explanation: string,
     *     alternate_date_rejection: ?string
     * }
     */
    public static function build(
        string $festivalKey,
        string $date,
        string $placeName,
        string $tradition,
        array $tithiWindow,
        array $anchorWindow,
        ?string $alternateDateRejection = null
    ): array {
        $rule = FestivalRuleRegistry::getRule($festivalKey);
        $name = $rule['name'] ?? $festivalKey;
        $scripture = $rule['scripture'] ?? 'Vedic Canon';
        $anchor = $rule['anchor'] ?? 'SUNRISE';

        $tithiStartLocal = $tithiWindow['starts_at']->setTimezone('Asia/Kolkata')->format('H:i');
        $tithiEndLocal = $tithiWindow['ends_at']->setTimezone('Asia/Kolkata')->format('H:i');
        $anchorStartLocal = $anchorWindow['starts_at']->setTimezone('Asia/Kolkata')->format('H:i');
        $anchorEndLocal = $anchorWindow['ends_at']->setTimezone('Asia/Kolkata')->format('H:i');

        $explanation = sprintf(
            '%s in %s is determined on %s under the %s tradition. Per %s, the observance is anchored to %s Kaal (%s to %s IST). The requisite Tithi is active from %s to %s IST, satisfying the primary canonical overlap requirement.',
            $name,
            $placeName,
            $date,
            $tradition,
            $scripture,
            $anchor,
            $anchorStartLocal,
            $anchorEndLocal,
            $tithiStartLocal,
            $tithiEndLocal
        );

        return [
            'festival' => $name,
            'date' => $date,
            'place' => $placeName,
            'tradition' => $tradition,
            'scripture_reference' => $scripture,
            'explanation' => $explanation,
            'alternate_date_rejection' => $alternateDateRejection,
        ];
    }
}

