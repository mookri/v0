<?php

namespace App\Modules\Pandit\Contracts;

/**
 * Canonical Vedic traditions recognized by the platform.
 */
enum VedicTradition: string
{
    case SMARTHA = 'SMARTHA';
    case VAISHNAVA = 'VAISHNAVA';
    case SHAIVA = 'SHAIVA';
    case SHAKTA = 'SHAKTA';
    case MADHVA = 'MADHVA';
    case RAMANUJA = 'RAMANUJA';
}

