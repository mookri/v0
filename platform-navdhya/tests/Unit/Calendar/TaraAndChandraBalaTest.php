<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Panchang\ChandraBalaCalculator;
use App\Domain\Calendar\Panchang\TaraBalaCalculator;
use PHPUnit\Framework\TestCase;

class TaraAndChandraBalaTest extends TestCase
{
    public function test_tara_bala_calculations_and_auspiciousness(): void
    {
        // Birth: 1 (Ashwini), Transit: 1 (Ashwini) -> Janma (slot 1)
        $t1 = TaraBalaCalculator::calculate(1, 1);
        $this->assertSame(1, $t1['tara_number']);
        $this->assertSame('JANMA', $t1['tara_name']);
        $this->assertFalse($t1['is_auspicious']);

        // Birth: 1 (Ashwini), Transit: 2 (Bharani) -> Sampat (slot 2)
        $t2 = TaraBalaCalculator::calculate(1, 2);
        $this->assertSame(2, $t2['tara_number']);
        $this->assertSame('SAMPAT', $t2['tara_name']);
        $this->assertTrue($t2['is_auspicious']);

        // Birth: 1 (Ashwini), Transit: 7 (Punarvasu) -> Naidhana (slot 7 - inauspicious)
        $t7 = TaraBalaCalculator::calculate(1, 7);
        $this->assertSame(7, $t7['tara_number']);
        $this->assertSame('NAIDHANA', $t7['tara_name']);
        $this->assertFalse($t7['is_auspicious']);

        // Across cycles: Birth: 1 (Ashwini), Transit: 11 (Purva Phalguni = 10 stars away -> slot 2 Sampat in cycle 2)
        $t11 = TaraBalaCalculator::calculate(1, 11);
        $this->assertSame(2, $t11['tara_number']);
        $this->assertSame('SAMPAT', $t11['tara_name']);
        $this->assertSame(2, $t11['cycle']);
        $this->assertTrue($t11['is_auspicious']);
    }

    public function test_chandra_bala_calculations_and_ashtama_chandra(): void
    {
        // Janma Rashi: 1 (Mesha), Transit: 1 (Mesha) -> 1st house (auspicious)
        $cb1 = ChandraBalaCalculator::calculate(1, 1);
        $this->assertSame(1, $cb1['house']);
        $this->assertTrue($cb1['is_auspicious']);
        $this->assertFalse($cb1['is_ashtama_chandra']);

        // Janma Rashi: 1 (Mesha), Transit: 8 (Vrishchika) -> 8th house (Ashtama Chandra - inauspicious)
        $cb8 = ChandraBalaCalculator::calculate(1, 8);
        $this->assertSame(8, $cb8['house']);
        $this->assertFalse($cb8['is_auspicious']);
        $this->assertTrue($cb8['is_ashtama_chandra']);

        // Janma Rashi: 1 (Mesha), Transit: 11 (Kumbha) -> 11th house (Labha - auspicious)
        $cb11 = ChandraBalaCalculator::calculate(1, 11);
        $this->assertSame(11, $cb11['house']);
        $this->assertTrue($cb11['is_auspicious']);
        $this->assertFalse($cb11['is_ashtama_chandra']);
    }
}

