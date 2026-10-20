<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Money value object (doc 02 §9): DECIMAL(18,4) + ISO-4217, bcmath,
 * half-up rounding, no floats.
 */
final class MoneyTest extends TestCase
{
    public function test_from_decimal_and_storage_form(): void
    {
        $money = Money::fromDecimal('1549.9', 'INR');

        $this->assertSame('15499000', $money->minorUnits());
        $this->assertSame('1549.9000', $money->toDecimal());
    }

    public function test_negative_amounts(): void
    {
        $money = Money::fromDecimal('-10.005', 'USD');

        $this->assertSame('-100050', $money->minorUnits());
        $this->assertSame('-10.0050', $money->toDecimal());
        $this->assertTrue($money->isNegative());
    }

    public function test_zero(): void
    {
        $this->assertTrue(Money::zero('INR')->isZero());
        $this->assertTrue(Money::fromDecimal('0.0000', 'INR')->isZero());
        $this->assertSame('0.0000', Money::zero('INR')->toDecimal());
    }

    public function test_add_and_subtract(): void
    {
        $total = Money::fromDecimal('100.50', 'INR')->add(Money::fromDecimal('0.25', 'INR'));

        $this->assertSame('100.7500', $total->toDecimal());
        $this->assertSame('100.5000', $total->subtract(Money::fromDecimal('0.25', 'INR'))->toDecimal());
    }

    public function test_mixed_currencies_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('1.00', 'INR')->add(Money::fromDecimal('1.00', 'USD'));
    }

    public function test_multiply_rounds_half_up(): void
    {
        // 5 minor units * 0.5 = 2.5 -> half-up -> 3.
        $money = new Money(5, 'INR');

        $this->assertSame('3', $money->multiply('0.5')->minorUnits());
        $this->assertSame('1180', (new Money(1000, 'INR'))->multiply('1.18')->minorUnits());
    }

    public function test_compare_and_equals(): void
    {
        $small = Money::fromDecimal('1.00', 'INR');
        $large = Money::fromDecimal('2.00', 'INR');

        $this->assertSame(-1, $small->compareTo($large));
        $this->assertSame(1, $large->compareTo($small));
        $this->assertTrue(Money::fromDecimal('1.0000', 'INR')->equals($small));
    }

    public function test_invalid_inputs_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('12.34567', 'INR'); // more than 4 dp
    }

    public function test_currency_must_be_iso4217_alpha3(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('1.00', 'inr');
    }
}
