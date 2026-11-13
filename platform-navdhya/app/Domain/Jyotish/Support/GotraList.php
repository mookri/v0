<?php

namespace App\Domain\Jyotish\Support;

/**
 * Pure domain Gotra list for Jyotish calculations (T-JY00-14).
 * Pure domain model — zero framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class GotraList
{
    /**
     * @var list<string>
     */
    public const CANONICAL = [
        'KASHYAPA',
        'BHARADWAJA',
        'VASISHTHA',
        'VISHWAMITRA',
        'GAUTAMA',
        'JAMADAGNI',
        'ATRI',
        'AGASTYA',
        'ANGIRASA',
        'HARITA',
        'KOUNDINYA',
        'SANDILYA',
        'VATSAYANA',
        'GARGA',
        'MUDGALA',
        'SHUNAKA',
        'PARASHARA',
        'UPAMANYU',
        'KAUSHIKA',
        'OTHER',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::CANONICAL;
    }

    public static function isValid(string $gotra): bool
    {
        return in_array(strtoupper(trim($gotra)), self::CANONICAL, true);
    }
}

