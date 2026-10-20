<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Money value object (doc 02 §9).
 *
 * Storage: DECIMAL(18,4) + ISO-4217 CHAR(3). Arithmetic: bcmath with
 * 4-decimal fixed-point, half-up rounding on finalize. Amounts are held
 * internally as minor-unit strings (integer strings, no separators) so
 * comparisons are exact; `toDecimal()` renders the 4-dp storage form.
 *
 * Rules (normative):
 *  - All operations require the same currency; mixed-currency math throws.
 *  - Immutable: every operation returns a new instance.
 *  - No float round-trips anywhere in the platform.
 */
final class Money
{
    private const SCALE = 4;

    private readonly string $minor;

    public function __construct(
        string|int $minorUnits,
        public readonly string $currency,
    ) {
        $this->assertCurrency($currency);

        $minor = (string) $minorUnits;

        if (preg_match('/^-?\d+$/', $minor) !== 1) {
            throw new InvalidArgumentException('Money minor units must be an integer string.');
        }

        $this->minor = $minor;
    }

    /**
     * Construct from a decimal string like "1549.9" or "1549.9000".
     */
    public static function fromDecimal(string $decimal, string $currency): self
    {
        if (preg_match('/^-?\d+(\.\d{1,4})?$/', $decimal) !== 1) {
            throw new InvalidArgumentException("Invalid decimal money value: {$decimal}");
        }

        $negative = str_starts_with($decimal, '-');
        $absolute = $negative ? substr($decimal, 1) : $decimal;

        [$whole, $fraction] = array_pad(explode('.', $absolute), 2, '');
        $fraction = str_pad($fraction, self::SCALE, '0');
        $minor = ltrim($whole.$fraction, '0');

        if ($minor === '') {
            return new self('0', $currency);
        }

        return new self($negative ? '-'.$minor : $minor, $currency);
    }

    public static function zero(string $currency): self
    {
        return new self('0', $currency);
    }

    public function minorUnits(): string
    {
        return $this->minor;
    }

    public function isZero(): bool
    {
        return (int) $this->minor === 0;
    }

    public function isNegative(): bool
    {
        return (int) $this->minor < 0;
    }

    public function isPositive(): bool
    {
        return (int) $this->minor > 0;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(bcadd($this->minor, $other->minor, 0), $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(bcsub($this->minor, $other->minor, 0), $this->currency);
    }

    /**
     * Multiply by a scalar factor given as a decimal string (e.g. "1.18").
     * Result is rounded half-up to minor units.
     */
    public function multiply(string $factor): self
    {
        if (preg_match('/^-?\d+(\.\d+)?$/', $factor) !== 1) {
            throw new InvalidArgumentException("Invalid money factor: {$factor}");
        }

        $product = bcmul($this->minor, $factor, self::SCALE + 2);

        return new self(self::roundHalfUp($product), $this->currency);
    }

    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return bccomp($this->minor, $other->minor, 0);
    }

    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * DECIMAL(18,4) storage representation ("1549.9000").
     */
    public function toDecimal(): string
    {
        $negative = str_starts_with($this->minor, '-');
        $abs = ltrim($negative ? substr($this->minor, 1) : $this->minor, '0');
        $abs = $abs === '' ? '0' : $abs;
        $whole = strlen($abs) > self::SCALE ? substr($abs, 0, -self::SCALE) : '0';
        $fraction = str_pad(strlen($abs) > self::SCALE ? substr($abs, -self::SCALE) : $abs, self::SCALE, '0', STR_PAD_LEFT);

        return ($negative && ($whole !== '0' || $fraction !== str_repeat('0', self::SCALE)) ? '-' : '').$whole.'.'.$fraction;
    }

    /**
     * @return array{amount_minor: string, currency: string}
     */
    public function toArray(): array
    {
        return [
            'amount_minor' => $this->minor,
            'currency' => $this->currency,
        ];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}."
            );
        }
    }

    private function assertCurrency(string $currency): void
    {
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new InvalidArgumentException("Currency must be ISO-4217 alpha-3: {$currency}");
        }
    }

    /**
     * Round a scaled decimal string to integer minor units, half-up
     * (away from zero for negatives — deterministic per doc 10 §2.3).
     */
    private static function roundHalfUp(string $value): string
    {
        $negative = str_starts_with($value, '-');
        $abs = $negative ? substr($value, 1) : $value;

        // Add half a minor unit, then truncate to integer minor units
        // (deterministic half-up, doc 10 §2.3).
        $rounded = bcadd($abs, '0.5', 0);

        return $negative ? '-'.$rounded : $rounded;
    }
}
