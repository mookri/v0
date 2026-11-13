<?php

namespace App\Modules\Customers\Domain\Enums;

use App\Modules\Pandit\Contracts\GotraList;

enum GotraEnum: string
{
    case KASHYAPA = 'KASHYAPA';
    case BHARADWAJA = 'BHARADWAJA';
    case VASISHTHA = 'VASISHTHA';
    case VISHWAMITRA = 'VISHWAMITRA';
    case GAUTAMA = 'GAUTAMA';
    case JAMADAGNI = 'JAMADAGNI';
    case ATRI = 'ATRI';
    case AGASTYA = 'AGASTYA';
    case ANGIRASA = 'ANGIRASA';
    case HARITA = 'HARITA';
    case KOUNDINYA = 'KOUNDINYA';
    case SANDILYA = 'SANDILYA';
    case VATSAYANA = 'VATSAYANA';
    case GARGA = 'GARGA';
    case MUDGALA = 'MUDGALA';
    case SHUNAKA = 'SHUNAKA';
    case PARASHARA = 'PARASHARA';
    case UPAMANYU = 'UPAMANYU';
    case KAUSHIKA = 'KAUSHIKA';
    case OTHER = 'OTHER';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

