<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Transit;

use App\Domain\Jyotish\Astronomy\Time\AstronomicalTime;
use App\Domain\Jyotish\Transit\GocharCalculator;
use App\Domain\Jyotish\Transit\SadeSatiDetector;
use PHPUnit\Framework\TestCase;

final class GocharAndSadeSatiTest extends TestCase
{
    public function test_gochar_transits_relative_to_natal_moon(): void
    {
        $calculator = new GocharCalculator();
        $jd = AstronomicalTime::calculateJulianDay(2026, 11, 13, 12, 0, 0);

        // Reference natal Moon in Aries (Rashi 1)
        $transits = $calculator->calculateTransits($jd, $jd + (69.184 / 86400.0), referenceRashi: 1);

        $this->assertArrayHasKey('sun', $transits);
        $this->assertArrayHasKey('jupiter', $transits);
        $this->assertArrayHasKey('saturn', $transits);

        foreach ($transits as $graha => $aspect) {
            $this->assertGreaterThanOrEqual(1, $aspect->natalHouse);
            $this->assertLessThanOrEqual(12, $aspect->natalHouse);
            $this->assertContains($aspect->nature, ['benefic', 'malefic']);
        }
    }

    public function test_sade_sati_detection_from_true_saturn_ephemeris(): void
    {
        $detector = new SadeSatiDetector();
        $jd = AstronomicalTime::calculateJulianDay(2026, 11, 13, 12, 0, 0);

        // In late 2026, Sidereal Saturn is in Pisces (Rashi 12)
        // If natal Moon is in Pisces (12), it should be PEAK (Janma Shani)
        $phasePeak = $detector->detectPhase($jd, $jd + (69.184 / 86400.0), natalMoonRashi: 12);
        $this->assertSame('PEAK', $phasePeak->phase);
        $this->assertTrue($phasePeak->isActive);
        $this->assertSame(1, $phasePeak->relativeHouse);

        // If natal Moon is in Aries (1), Pisces is 12th from Aries => RISING
        $phaseRising = $detector->detectPhase($jd, $jd + (69.184 / 86400.0), natalMoonRashi: 1);
        $this->assertSame('RISING', $phaseRising->phase);
        $this->assertTrue($phaseRising->isActive);
        $this->assertSame(12, $phaseRising->relativeHouse);

        // If natal Moon is in Leo (5), Pisces is 8th => DHAIYA_8TH (Ashtama Shani)
        $phaseAshtama = $detector->detectPhase($jd, $jd + (69.184 / 86400.0), natalMoonRashi: 5);
        $this->assertSame('DHAIYA_8TH', $phaseAshtama->phase);
        $this->assertTrue($phaseAshtama->isActive);

        // If natal Moon is in Gemini (3), Pisces is 10th => NONE
        $phaseNone = $detector->detectPhase($jd, $jd + (69.184 / 86400.0), natalMoonRashi: 3);
        $this->assertSame('NONE', $phaseNone->phase);
        $this->assertFalse($phaseNone->isActive);
    }
}

