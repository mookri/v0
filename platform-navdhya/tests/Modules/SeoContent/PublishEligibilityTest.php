<?php

namespace Tests\Modules\SeoContent;

use App\Modules\SeoContent\Support\JsonLdGenerator;
use App\Modules\SeoContent\Support\SeoPublishEligibilityGate;
use App\Modules\SeoContent\Support\SimHashGenerator;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PublishEligibilityTest extends TestCase
{
    public function test_publish_eligibility_gate_limits_indexation_closes_p13(): void
    {
        // 1. Valid verified place within near horizon (tomorrow)
        $tomorrow = CarbonImmutable::now('UTC')->addDay();
        $evalValid = SeoPublishEligibilityGate::evaluatePanchangDay('delhi', $tomorrow);
        $this->assertTrue($evalValid['is_indexable']);
        $this->assertSame('index,follow', $evalValid['robots_directive']);

        // 2. Unverified place -> noindex
        $evalUnverified = SeoPublishEligibilityGate::evaluatePanchangDay('atlantis_unverified', $tomorrow);
        $this->assertFalse($evalUnverified['is_indexable']);
        $this->assertSame('noindex,follow', $evalUnverified['robots_directive']);

        // 3. Date 90 days in future -> noindex
        $farFuture = CarbonImmutable::now('UTC')->addDays(90);
        $evalFar = SeoPublishEligibilityGate::evaluatePanchangDay('delhi', $farFuture);
        $this->assertFalse($evalFar['is_indexable']);
        $this->assertSame('noindex,follow', $evalFar['robots_directive']);
    }

    public function test_simhash_near_duplicate_doorway_detector(): void
    {
        $textDelhi = 'Today Panchang in New Delhi auspicious timings Rahu Kaal Abhijit Muhurat Choghadiya for marriage ceremonies.';
        $textNoidaDoorway = 'Today Panchang in Noida auspicious timings Rahu Kaal Abhijit Muhurat Choghadiya for marriage ceremonies.';

        $distance = SimHashGenerator::hammingDistance(
            SimHashGenerator::fingerprint($textDelhi),
            SimHashGenerator::fingerprint($textNoidaDoorway)
        );

        // Identical text has 0 distance
        $this->assertSame(0, SimHashGenerator::hammingDistance(
            SimHashGenerator::fingerprint($textDelhi),
            SimHashGenerator::fingerprint($textDelhi)
        ));

        // Near-duplicate doorway text (single word swap) has small Hamming distance (<= 10 out of 64 bits)
        $this->assertLessThanOrEqual(10, $distance);

        $distinctText = 'Comprehensive astrological treatise regarding sidereal ayanamsa calculations according to Surya Siddhanta and modern ephemerides.';
        $distanceDistinct = SimHashGenerator::hammingDistance(
            SimHashGenerator::fingerprint($textDelhi),
            SimHashGenerator::fingerprint($distinctText)
        );

        $this->assertGreaterThan(15, $distanceDistinct);
        $this->assertTrue(SimHashGenerator::isSufficientlyUnique($textDelhi, $distinctText));
    }

    public function test_json_ld_breadcrumbs_and_truthful_dates_closes_p12(): void
    {
        $crumbs = [
            ['name' => 'Home', 'url' => 'https://navdhya.com/'],
            ['name' => 'Panchang', 'url' => 'https://navdhya.com/panchang/'],
            ['name' => 'Delhi', 'url' => 'https://navdhya.com/panchang/delhi/'],
        ];

        $jsonLd = JsonLdGenerator::breadcrumbs($crumbs);
        $this->assertSame('BreadcrumbList', $jsonLd['@type']);
        $this->assertCount(3, $jsonLd['itemListElement']);
        $this->assertSame('https://navdhya.com/panchang/delhi', $jsonLd['itemListElement'][2]['item']);

        $event = JsonLdGenerator::festivalEvent(
            name: 'Diwali 2026',
            description: 'Lakshmi Puja on Amavasya',
            canonicalUrl: 'https://navdhya.com/festivals/diwali-2026/',
            startDate: CarbonImmutable::parse('2026-11-08T18:00:00Z'),
            endDate: CarbonImmutable::parse('2026-11-08T20:24:00Z'),
            placeName: 'Varanasi',
            truthfulDateModified: CarbonImmutable::parse('2026-06-01T10:00:00Z')
        );

        $this->assertSame('Event', $event['@type']);
        $this->assertSame('https://navdhya.com/festivals/diwali-2026', $event['url']);
        $this->assertSame('2026-06-01T10:00:00+00:00', $event['dateModified']);
    }
}
