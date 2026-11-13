<?php

namespace Tests\Unit\Jyotish\Astronomy;

use App\Domain\Jyotish\Astronomy\BirthProfileNormalizer;
use App\Domain\Jyotish\Exceptions\AmbiguousLocalTimeException;
use App\Domain\Jyotish\Exceptions\AmbiguousPlaceException;
use App\Domain\Jyotish\Exceptions\InvalidBirthTimeException;
use App\Domain\Jyotish\Exceptions\InvalidCoordinatesException;
use App\Domain\Jyotish\Geography\PlaceResolver;
use PHPUnit\Framework\TestCase;

/**
 * Birth Profile Normalization Unit Tests (T-JY01-01).
 * Verifies AC-T-JY01-01, AC-T-JY01-01b, AC-T-JY01-01c.
 */
final class BirthProfileNormalizerTest extends TestCase
{
    private BirthProfileNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new BirthProfileNormalizer(new PlaceResolver());
    }

    public function test_normalizes_canonical_hub_with_local_time(): void
    {
        $profile = $this->normalizer->normalize([
            'birth_place' => 'Varanasi',
            'year' => 2026,
            'month' => 11,
            'day' => 24,
            'hour' => 17,
            'minute' => 30,
            'second' => 0,
        ]);

        $this->assertSame('hub_in_vns', $profile['place_id']);
        $this->assertSame('Varanasi', $profile['raw_birth_place_text']);
        $this->assertEqualsWithDelta(25.3176, $profile['latitude'], 0.0001);
        $this->assertEqualsWithDelta(82.9739, $profile['longitude'], 0.0001);
        $this->assertSame('Asia/Kolkata', $profile['iana_timezone']);
        // 17:30 IST (+5:30) is 12:00 UTC
        $this->assertSame('2026-11-24T12:00:00.000000Z', $profile['utc_datetime']);
        $this->assertGreaterThan(2451545.0, $profile['astronomical_time']->tt->value);
    }

    public function test_ambiguous_place_throws_ambiguous_place_exception(): void
    {
        try {
            $this->normalizer->normalize([
                'birth_place' => 'Hyderabad',
                'year' => 2026,
                'month' => 1,
                'day' => 1,
                'hour' => 12,
            ]);
            $this->fail('Expected AmbiguousPlaceException was not thrown.');
        } catch (AmbiguousPlaceException $e) {
            $this->assertSame('Hyderabad', $e->query);
            $this->assertCount(2, $e->candidates);
            $placeIds = array_column($e->candidates, 'place_id');
            $this->assertContains('hub_in_hyd', $placeIds);
            $this->assertContains('hub_pk_hyd', $placeIds);
        }
    }

    public function test_disambiguated_place_id_resolves_successfully(): void
    {
        $profile = $this->normalizer->normalize([
            'birth_place' => 'Hyderabad',
            'place_id' => 'hub_in_hyd',
            'year' => 2026,
            'month' => 1,
            'day' => 1,
            'hour' => 12,
        ]);

        $this->assertSame('hub_in_hyd', $profile['place_id']);
        $this->assertSame('Asia/Kolkata', $profile['iana_timezone']);
    }

    public function test_invalid_coordinates_rejected(): void
    {
        $this->expectException(InvalidCoordinatesException::class);
        $this->normalizer->normalize([
            'latitude' => 95.0,
            'longitude' => 77.0,
            'year' => 2026,
            'month' => 1,
            'day' => 1,
        ]);
    }

    public function test_spring_forward_gap_throws_invalid_birth_time_exception(): void
    {
        // London DST jump on 2026-03-29: 01:00 jumps to 02:00
        $this->expectException(InvalidBirthTimeException::class);
        $this->normalizer->normalize([
            'birth_place' => 'London',
            'year' => 2026,
            'month' => 3,
            'day' => 29,
            'hour' => 1,
            'minute' => 30,
        ]);
    }

    public function test_fallback_fold_requires_fold_selection(): void
    {
        // London DST fallback on 2026-10-25: 02:00 jumps back to 01:00 (01:00-02:00 repeats)
        $this->expectException(AmbiguousLocalTimeException::class);
        $this->normalizer->normalize([
            'birth_place' => 'London',
            'year' => 2026,
            'month' => 10,
            'day' => 25,
            'hour' => 1,
            'minute' => 30,
            'fold_selection' => null,
        ]);
    }

    public function test_fallback_fold_selection_resolves_distinct_utc_instants(): void
    {
        $fold0 = $this->normalizer->normalize([
            'birth_place' => 'London',
            'year' => 2026,
            'month' => 10,
            'day' => 25,
            'hour' => 1,
            'minute' => 30,
            'fold_selection' => 0,
        ]);

        $fold1 = $this->normalizer->normalize([
            'birth_place' => 'London',
            'year' => 2026,
            'month' => 10,
            'day' => 25,
            'hour' => 1,
            'minute' => 30,
            'fold_selection' => 1,
        ]);

        $this->assertSame(0, $fold0['fold_resolved']);
        $this->assertSame(1, $fold1['fold_resolved']);

        // First fold is BST (UTC+1, so 00:30 UTC), second is GMT (UTC+0, so 01:30 UTC)
        $this->assertSame('2026-10-25T00:30:00.000000Z', $fold0['utc_datetime']);
        $this->assertSame('2026-10-25T01:30:00.000000Z', $fold1['utc_datetime']);
    }
}

