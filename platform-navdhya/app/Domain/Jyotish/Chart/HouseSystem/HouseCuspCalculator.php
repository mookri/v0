<?php

namespace App\Domain\Jyotish\Chart\HouseSystem;

use InvalidArgumentException;

/**
 * House Cusp Factory & Calculator Registry (T-JY03-02).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class HouseCuspCalculator
{
    public const SYSTEM_WHOLE_SIGN = 'WHOLE_SIGN';
    public const SYSTEM_SRIPATI = 'SRIPATI';
    public const SYSTEM_PLACIDUS = 'PLACIDUS';
    public const SYSTEM_EQUAL_HOUSE = 'EQUAL_HOUSE';

    public const SUPPORTED_SYSTEMS = [
        self::SYSTEM_WHOLE_SIGN,
        self::SYSTEM_SRIPATI,
        self::SYSTEM_PLACIDUS,
        self::SYSTEM_EQUAL_HOUSE,
    ];

    /**
     * Resolve the house system strategy for a given system code.
     */
    public static function forSystem(string $code): HouseSystemInterface
    {
        $normalized = strtoupper(trim($code));

        return match ($normalized) {
            self::SYSTEM_WHOLE_SIGN, 'WHOLE' => new WholeSignSystem(),
            self::SYSTEM_SRIPATI => new SripatiSystem(),
            self::SYSTEM_PLACIDUS => new PlacidusSystem(),
            self::SYSTEM_EQUAL_HOUSE, 'EQUAL' => new EqualHouseSystem(),
            default => throw new InvalidArgumentException("Unsupported house system: '{$code}'. Supported: " . implode(', ', self::SUPPORTED_SYSTEMS)),
        };
    }
}

