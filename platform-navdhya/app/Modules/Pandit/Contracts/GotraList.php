<?php

namespace App\Modules\Pandit\Contracts;

/**
 * Canonical list of Vedic gotras.
 * Primary sapta/ashta-rishi lineages + widely documented classical gotras.
 */
final class GotraList
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
