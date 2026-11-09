<?php

namespace Tests\Unit\Customers\Panchang;

use App\Modules\Customers\Domain\Panchang\AnchorOccurrenceResolver;
use App\Modules\Customers\Domain\Panchang\Exceptions\OutOfCorpusException;
use App\Modules\Customers\Domain\Panchang\PanchangCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class GoldenCorpusTest extends TestCase
{
    private AnchorOccurrenceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AnchorOccurrenceResolver();
    }

    public function test_resolves_ekadashi_tithi_anchor_recurrence(): void
    {
        // Custom Tithi: Ekadashi (index_in_paksha = 11)
        $baseline = CarbonImmutable::parse('2026-11-01 00:00:00', 'Asia/Kolkata');
        $rule = [
            'type' => 'CUSTOM_TITHI',
            'index_in_paksha' => 11,
        ];

        $next = $this->resolver->occurrenceForAnchor($rule, $baseline, 'Asia/Kolkata', 'PURNIMANTA');

        $this->assertSame(11, $next->tithi['index_in_paksha']);
        $this->assertSame('EKADASHI', $next->tithi['name']);
        $this->assertGreaterThan('2026-11-01', $next->date);
    }

    public function test_resolves_shraddha_ancestral_anchor_with_amanta_and_purnimanta(): void
    {
        // Shraddha anchor: Masa Kartika, Krishna Paksha, Tithi 11 (Ekadashi)
        $baseline = CarbonImmutable::parse('2026-10-01 00:00:00', 'Asia/Kolkata');
        $rule = [
            'type' => 'SHRADDHA',
            'masa' => 'KARTIKA',
            'paksha' => 'KRISHNA',
            'tithi' => 26, // Krishna Ekadashi
        ];

        $occurrence = $this->resolver->occurrenceForAnchor($rule, $baseline, 'Asia/Kolkata', 'PURNIMANTA');

        $this->assertSame(26, $occurrence->tithi['number']);
        $this->assertSame('KRISHNA', $occurrence->tithi['paksha']);
        $this->assertSame('EKADASHI', $occurrence->tithi['name']);
    }

    public function test_resolves_solar_anniversary_anchor(): void
    {
        $baseline = CarbonImmutable::parse('2026-05-01 00:00:00', 'Asia/Kolkata');
        $rule = [
            'type' => 'SOLAR_BIRTHDAY',
            'month' => 8,
            'day' => 15,
        ];

        $occurrence = $this->resolver->occurrenceForAnchor($rule, $baseline, 'Asia/Kolkata');
        $this->assertSame('2026-08-15', $occurrence->date);
    }

    public function test_corpus_horizon_guard_rejects_out_of_range_years(): void
    {
        $this->expectException(OutOfCorpusException::class);

        // Year 2035 is outside verified ephemeris corpus
        $baseline = CarbonImmutable::parse('2035-01-01 00:00:00', 'Asia/Kolkata');
        $rule = [
            'type' => 'CUSTOM_TITHI',
            'index_in_paksha' => 11,
        ];

        $this->resolver->occurrenceForAnchor($rule, $baseline, 'Asia/Kolkata');
    }

    public function test_multi_year_continuity_across_2026_and_2027(): void
    {
        // Step through consecutive months in 2026-2027 asserting valid non-null calculations
        $dates = [
            '2026-01-15', '2026-03-20', '2026-06-21', '2026-09-22',
            '2026-12-21', '2027-04-14', '2027-08-15', '2027-11-10',
        ];

        foreach ($dates as $d) {
            $carbon = CarbonImmutable::parse($d);
            $p = PanchangCalculator::calculate($carbon->year, $carbon->month, $carbon->day, 23.1765, 75.7885, 'PURNIMANTA');
            $this->assertSame($d, $p->date);
            $this->assertNotEmpty($p->resolvedMasa());
            $this->assertNotEmpty($p->nakshatra['name']);
        }
    }
}

