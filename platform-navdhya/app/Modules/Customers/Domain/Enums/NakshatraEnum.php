<?php

namespace App\Modules\Customers\Domain\Enums;

enum NakshatraEnum: string
{
    case ASHWINI = 'ASHWINI';
    case BHARANI = 'BHARANI';
    case KRITTIKA = 'KRITTIKA';
    case ROHINI = 'ROHINI';
    case MRIGASHIRA = 'MRIGASHIRA';
    case ARDRA = 'ARDRA';
    case PUNARVASU = 'PUNARVASU';
    case PUSHYA = 'PUSHYA';
    case ASHLESHA = 'ASHLESHA';
    case MAGHA = 'MAGHA';
    case PURVA_PHALGUNI = 'PURVA_PHALGUNI';
    case UTTARA_PHALGUNI = 'UTTARA_PHALGUNI';
    case HASTA = 'HASTA';
    case CHITRA = 'CHITRA';
    case SWATI = 'SWATI';
    case VISHAKHA = 'VISHAKHA';
    case ANURADHA = 'ANURADHA';
    case JYESHTHA = 'JYESHTHA';
    case MULA = 'MULA';
    case PURVA_ASHADHA = 'PURVA_ASHADHA';
    case UTTARA_ASHADHA = 'UTTARA_ASHADHA';
    case SHRAVANA = 'SHRAVANA';
    case DHANISHTA = 'DHANISHTA';
    case SHATABHISHA = 'SHATABHISHA';
    case PURVA_BHADRAPADA = 'PURVA_BHADRAPADA';
    case UTTARA_BHADRAPADA = 'UTTARA_BHADRAPADA';
    case REVATI = 'REVATI';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

