<?php

namespace App\Domain\Calendar\Astronomy;

enum HorizonStatusEnum: string
{
    case NORMAL = 'normal';
    case MIDNIGHT_SUN = 'midnight_sun';
    case POLAR_NIGHT = 'polar_night';
    case UNSUPPORTED_LATITUDE = 'unsupported_latitude';
}

