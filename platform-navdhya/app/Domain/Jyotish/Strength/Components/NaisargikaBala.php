<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Strength\Components;

final readonly class NaisargikaBala
{
    /**
     * Fixed Natural Strength (Naisargika Bala) in Virupas per BPHS Ch. 27:
     * Sun: 60.0 Virupas (1.000 Rupa)
     * Moon: 51.43 Virupas (0.857 Rupa)
     * Venus: 42.86 Virupas (0.714 Rupa)
     * Jupiter: 34.29 Virupas (0.571 Rupa)
     * Mercury: 25.71 Virupas (0.429 Rupa)
     * Mars: 17.14 Virupas (0.286 Rupa)
     * Saturn: 8.57 Virupas (0.143 Rupa)
     */
    public const VIRUPAS = [
        'sun' => 60.0,
        'moon' => 51.43,
        'venus' => 42.86,
        'jupiter' => 34.29,
        'mercury' => 25.71,
        'mars' => 17.14,
        'saturn' => 8.57,
    ];

    public static function get(string $graha): float
    {
        return self::VIRUPAS[strtolower($graha)] ?? 0.0;
    }
}

