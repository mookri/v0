<?php

namespace App\Domain\Jyotish\Astronomy;

/**
 * Horizon Status for Arctic/Antarctic & Standard Latitudes (T-JY03-01).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
enum HorizonStatusEnum: string
{
    case NORMAL = 'normal';
    case MIDNIGHT_SUN = 'midnight_sun';
    case POLAR_NIGHT = 'polar_night';
    case UNSUPPORTED_POLAR = 'unsupported_polar';
}

