<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Astronomy;

use App\Domain\Jyotish\Support\Microdegrees;
use PHPUnit\Framework\TestCase;

/**
 * Validates integer microdegrees boundary precision (T-JYOPT-11 / AC-T-JYOPT-11).
 * Proves that borderline coordinates ($29^\circ 59' 59.999''$) are never misclassified.
 */
final class IntegerMicrodegreesBoundaryPrecisionTest extends TestCase
{
    public function test_microdegrees_boundary_preserves_sign_and_nakshatra_indices(): void
    {
        // 1. Exactly at boundary threshold 29.999999° (Aries / Mesha, last microsecond before Taurus)
        $ariesEndDeg = 29.999999;
        $uDegAries = Microdegrees::fromDegrees($ariesEndDeg);
        $this->assertSame(0, Microdegrees::rashiIndex($uDegAries), '29.999999 deg must remain Mesha (index 0)');

        // 2. Exactly at 30.000000° (Taurus / Vrishabha, index 1)
        $taurusStartDeg = 30.0;
        $uDegTaurus = Microdegrees::fromDegrees($taurusStartDeg);
        $this->assertSame(1, Microdegrees::rashiIndex($uDegTaurus), '30.0 deg must be Vrishabha (index 1)');

        // 3. Nakshatra boundary: Ashwini ends at 13° 20' = 13.333333333°
        // 48,000,000 microdegrees
        $ashwiniEndUdeg = Microdegrees::NAKSHATRA_SPAN - 1;
        $this->assertSame(0, Microdegrees::nakshatraIndex($ashwiniEndUdeg), 'Just before 13°20\' must be Ashwini (index 0)');
        $this->assertSame(4, Microdegrees::pada($ashwiniEndUdeg), 'Must be 4th Pada of Ashwini');

        $bharaniStartUdeg = Microdegrees::NAKSHATRA_SPAN;
        $this->assertSame(1, Microdegrees::nakshatraIndex($bharaniStartUdeg), 'Exactly 13°20\' must be Bharani (index 1)');
        $this->assertSame(1, Microdegrees::pada($bharaniStartUdeg), 'Must be 1st Pada of Bharani');
    }

    public function test_bidirectional_conversion_preserves_precision(): void
    {
        $testDegrees = [0.0, 13.3333333333, 29.999999, 180.5, 359.999999];
        foreach ($testDegrees as $deg) {
            $uDeg = Microdegrees::fromDegrees($deg);
            $recovered = Microdegrees::toDegrees($uDeg);
            $this->assertEqualsWithDelta($deg, $recovered, 1e-6);
        }
    }
}

