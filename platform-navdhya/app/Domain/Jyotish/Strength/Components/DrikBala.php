<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Strength\Components;

final readonly class DrikBala
{
    /**
     * Compute Aspectual Strength (Drik Bala) in Virupas.
     * Benefic aspects add to Drik Bala; malefic aspects subtract from it.
     * Value is derived from the aspect angle: Drik Bala = (Benefic Aspect - Malefic Aspect) / 4.
     *
     * @param float $beneficAspectDrishti Total benefic drishti received (in Virupas)
     * @param float $maleficAspectDrishti Total malefic drishti received (in Virupas)
     * @return float
     */
    public static function calculate(float $beneficAspectDrishti, float $maleficAspectDrishti): float
    {
        $drikBala = ($beneficAspectDrishti - $maleficAspectDrishti) / 4.0;
        return round($drikBala, 2);
    }
}

