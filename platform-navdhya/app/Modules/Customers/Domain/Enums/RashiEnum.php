<?php

namespace App\Modules\Customers\Domain\Enums;

enum RashiEnum: string
{
    case MESHA = 'MESHA';
    case VRISHABHA = 'VRISHABHA';
    case MITHUNA = 'MITHUNA';
    case KARKA = 'KARKA';
    case SIMHA = 'SIMHA';
    case KANYA = 'KANYA';
    case TULA = 'TULA';
    case VRISHCHIKA = 'VRISHCHIKA';
    case DHANU = 'DHANU';
    case MAKARA = 'MAKARA';
    case KUMBHA = 'KUMBHA';
    case MEENA = 'MEENA';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

