<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Panchang\MultiYearAdhikaProjection;
use PHPUnit\Framework\TestCase;

class AdhikaMasaProjectionTest extends TestCase
{
    public function test_multi_year_adhika_masa_projections(): void
    {
        $proj2026 = MultiYearAdhikaProjection::forYear(2026);
        $this->assertNotNull($proj2026);
        $this->assertSame('JYESHTHA', $proj2026['adhika_masa']);

        $proj2029 = MultiYearAdhikaProjection::forYear(2029);
        $this->assertNotNull($proj2029);
        $this->assertSame('CHAITRA', $proj2029['adhika_masa']);

        $proj2027 = MultiYearAdhikaProjection::forYear(2027);
        $this->assertNull($proj2027, '2027 is a standard year without Adhika Masa');
    }
}

