<?php

namespace Tests\Modules\Customers;

use App\Domain\Calendar\Panchang\AnchorOccurrenceResolver;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Validates place-aware anchor occurrence resolution (T-JYAUT-00b / D-13).
 * Proves that identical anchor rules evaluated across London, Ujjain, and Tokyo
 * produce distinct topocentric solar sunrise times rather than fixed Ujjain times.
 */
final class PlaceAwareAnchorOccurrenceTest extends TestCase
{
    public function test_identical_anchor_rules_yield_distinct_place_aware_sunrises(): void
    {
        $resolver = new AnchorOccurrenceResolver();
        $baseline = CarbonImmutable::parse('2026-03-01 00:00:00', 'UTC');

        // Anchor rule: Ekadashi (index 11)
        $rule = [
            'type' => 'CUSTOM_TITHI',
            'index_in_paksha' => 11,
            'tradition' => 'PURNIMANTA',
        ];

        // 1. London (UTC)
        $londonRule = array_merge($rule, [
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'timezone' => 'Europe/London',
        ]);
        $londonOccurrence = $resolver->occurrenceForAnchor($londonRule, $baseline, 'Europe/London');

        // 2. Ujjain (Asia/Kolkata)
        $ujjainRule = array_merge($rule, [
            'latitude' => 23.1765,
            'longitude' => 75.7885,
            'timezone' => 'Asia/Kolkata',
        ]);
        $ujjainOccurrence = $resolver->occurrenceForAnchor($ujjainRule, $baseline, 'Asia/Kolkata');

        // 3. Tokyo (Asia/Tokyo)
        $tokyoRule = array_merge($rule, [
            'latitude' => 35.6762,
            'longitude' => 139.6503,
            'timezone' => 'Asia/Tokyo',
        ]);
        $tokyoOccurrence = $resolver->occurrenceForAnchor($tokyoRule, $baseline, 'Asia/Tokyo');

        // Sunrise UTC instants must be populated
        $this->assertNotEmpty($londonOccurrence->sunriseUtc);
        $this->assertNotEmpty($ujjainOccurrence->sunriseUtc);
        $this->assertNotEmpty($tokyoOccurrence->sunriseUtc);

        // Sunrises must differ across distinct geographical coordinates
        $this->assertNotSame(
            $londonOccurrence->sunriseUtc,
            $ujjainOccurrence->sunriseUtc,
            'London sunrise must not equal Ujjain sunrise'
        );

        $this->assertNotSame(
            $tokyoOccurrence->sunriseUtc,
            $ujjainOccurrence->sunriseUtc,
            'Tokyo sunrise must not equal Ujjain sunrise'
        );
    }
}
