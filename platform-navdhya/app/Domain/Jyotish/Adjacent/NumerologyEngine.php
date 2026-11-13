<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Adjacent;

/**
 * Numerology Engine (T-JY10-07).
 * Pure mathematical calculations for Life Path, Destiny, and Soul Urge numbers.
 * Strictly segregated from astronomical ephemeris and ayanamsa layers.
 */
final readonly class NumerologyEngine
{
    private const CHALDEAN_MAP = [
        'A' => 1, 'I' => 1, 'J' => 1, 'Q' => 1, 'Y' => 1,
        'B' => 2, 'K' => 2, 'R' => 2,
        'C' => 3, 'G' => 3, 'L' => 3, 'S' => 3,
        'D' => 4, 'M' => 4, 'T' => 4,
        'E' => 5, 'H' => 5, 'N' => 5, 'X' => 5,
        'U' => 6, 'V' => 6, 'W' => 6,
        'O' => 7, 'Z' => 7,
        'F' => 8, 'P' => 8,
    ];

    /**
     * Compute Life Path number from birthdate (YYYY-MM-DD).
     */
    public static function calculateLifePath(int $year, int $month, int $day): int
    {
        $sum = self::reduceToSingleDigit($year) + self::reduceToSingleDigit($month) + self::reduceToSingleDigit($day);
        return self::reduceToSingleDigit($sum, allowMasterNumbers: true);
    }

    /**
     * Compute Chaldean name number.
     */
    public static function calculateChaldeanNameNumber(string $fullName): int
    {
        $sanitized = strtoupper(preg_replace('/[^a-zA-Z]/', '', $fullName) ?? '');
        $sum = 0;
        $len = strlen($sanitized);
        for ($i = 0; $i < $len; $i++) {
            $char = $sanitized[$i];
            $sum += self::CHALDEAN_MAP[$char] ?? 0;
        }

        return self::reduceToSingleDigit($sum, allowMasterNumbers: false);
    }

    private static function reduceToSingleDigit(int $number, bool $allowMasterNumbers = false): int
    {
        while ($number > 9) {
            if ($allowMasterNumbers && in_array($number, [11, 22, 33], true)) {
                return $number;
            }
            $digits = str_split((string) $number);
            $number = array_sum(array_map('intval', $digits));
        }

        return $number;
    }
}

