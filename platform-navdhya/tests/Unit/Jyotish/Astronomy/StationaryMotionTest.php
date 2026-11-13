<?php

namespace Tests\Unit\Jyotish\Astronomy;

use App\Domain\Jyotish\Astronomy\PlanetaryMotionClassifier;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Planetary Motion Classifier (T-JY04-02).
 */
final class StationaryMotionTest extends TestCase
{
    public function test_sun_and_moon_are_never_retrograde(): void
    {
        $sun = PlanetaryMotionClassifier::classify('sun', -0.5);
        $this->assertFalse($sun['is_retrograde']);
        $this->assertSame(PlanetaryMotionClassifier::MOTION_DIRECT, $sun['motion']);

        $moon = PlanetaryMotionClassifier::classify('moon', -1.2);
        $this->assertFalse($moon['is_retrograde']);
        $this->assertSame(PlanetaryMotionClassifier::MOTION_DIRECT, $moon['motion']);
    }

    public function test_retrograde_and_direct_classification(): void
    {
        $retroMars = PlanetaryMotionClassifier::classify('mars', -0.25);
        $this->assertTrue($retroMars['is_retrograde']);
        $this->assertFalse($retroMars['is_stationary']);
        $this->assertSame(PlanetaryMotionClassifier::MOTION_RETROGRADE, $retroMars['motion']);

        $directJupiter = PlanetaryMotionClassifier::classify('jupiter', 0.12);
        $this->assertFalse($directJupiter['is_retrograde']);
        $this->assertFalse($directJupiter['is_stationary']);
        $this->assertSame(PlanetaryMotionClassifier::MOTION_DIRECT, $directJupiter['motion']);
    }

    public function test_stationary_velocity_detection(): void
    {
        // Very low velocity below threshold (0.008°/day)
        $stationSaturn = PlanetaryMotionClassifier::classify('saturn', 0.003, -0.001);
        $this->assertTrue($stationSaturn['is_stationary']);
        $this->assertSame(PlanetaryMotionClassifier::MOTION_STATIONARY_RETROGRADE, $stationSaturn['motion']);

        $stationMercury = PlanetaryMotionClassifier::classify('mercury', -0.002, 0.005);
        $this->assertTrue($stationMercury['is_stationary']);
        $this->assertSame(PlanetaryMotionClassifier::MOTION_STATIONARY_DIRECT, $stationMercury['motion']);
    }
}

