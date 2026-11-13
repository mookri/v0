<?php

namespace App\Domain\Jyotish\Rules\Datasets;

/**
 * Classical Parashari Planetary Dignities Dataset (BPHS Chapter 3 & 45).
 * Pure domain constants — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class ParashariDignities
{
    /**
     * Exaltation sign index (0..11) and deep exaltation degree (0..30).
     */
    public const EXALTATIONS = [
        'sun' => ['rashi' => 0, 'degree' => 10.0],       // Mesha 10°
        'moon' => ['rashi' => 1, 'degree' => 3.0],       // Vrishabha 3°
        'mars' => ['rashi' => 9, 'degree' => 28.0],      // Makara 28°
        'mercury' => ['rashi' => 5, 'degree' => 15.0],   // Kanya 15°
        'jupiter' => ['rashi' => 3, 'degree' => 5.0],    // Karkataka 5°
        'venus' => ['rashi' => 11, 'degree' => 27.0],    // Meena 27°
        'saturn' => ['rashi' => 6, 'degree' => 20.0],    // Tula 20°
        'rahu' => ['rashi' => 1, 'degree' => 20.0],      // Vrishabha 20°
        'ketu' => ['rashi' => 7, 'degree' => 20.0],      // Vrishchika 20°
    ];

    /**
     * Debilitation sign index (0..11) and deep debilitation degree (0..30).
     */
    public const DEBILITATIONS = [
        'sun' => ['rashi' => 6, 'degree' => 10.0],       // Tula 10°
        'moon' => ['rashi' => 7, 'degree' => 3.0],       // Vrishchika 3°
        'mars' => ['rashi' => 3, 'degree' => 28.0],      // Karkataka 28°
        'mercury' => ['rashi' => 11, 'degree' => 15.0],  // Meena 15°
        'jupiter' => ['rashi' => 9, 'degree' => 5.0],    // Makara 5°
        'venus' => ['rashi' => 5, 'degree' => 27.0],     // Kanya 27°
        'saturn' => ['rashi' => 0, 'degree' => 20.0],    // Mesha 20°
        'rahu' => ['rashi' => 7, 'degree' => 20.0],      // Vrishchika 20°
        'ketu' => ['rashi' => 1, 'degree' => 20.0],      // Vrishabha 20°
    ];

    /**
     * Moolatrikona sign and degree ranges [min, max].
     */
    public const MOOLATRIKONA = [
        'sun' => ['rashi' => 4, 'min' => 0.0, 'max' => 20.0],      // Simha 0°-20°
        'moon' => ['rashi' => 1, 'min' => 3.0, 'max' => 30.0],     // Vrishabha 3°-30°
        'mars' => ['rashi' => 0, 'min' => 0.0, 'max' => 12.0],     // Mesha 0°-12°
        'mercury' => ['rashi' => 5, 'min' => 15.0, 'max' => 20.0], // Kanya 15°-20°
        'jupiter' => ['rashi' => 8, 'min' => 0.0, 'max' => 10.0],  // Dhanu 0°-10°
        'venus' => ['rashi' => 6, 'min' => 0.0, 'max' => 15.0],    // Tula 0°-15°
        'saturn' => ['rashi' => 10, 'min' => 0.0, 'max' => 20.0],  // Kumbha 0°-20°
    ];

    /**
     * Own signs (Swakshetra) for each planet.
     */
    public const OWN_SIGNS = [
        'sun' => [4],              // Simha
        'moon' => [3],             // Karkataka
        'mars' => [0, 7],          // Mesha, Vrishchika
        'mercury' => [2, 5],       // Mithuna, Kanya
        'jupiter' => [8, 11],      // Dhanu, Meena
        'venus' => [1, 6],         // Vrishabha, Tula
        'saturn' => [9, 10],       // Makara, Kumbha
        'rahu' => [10],            // Kumbha (co-lord with Saturn)
        'ketu' => [7],             // Vrishchika (co-lord with Mars)
    ];

    /**
     * Natural permanent friendships (Naisargika Sambandha).
     */
    public const NATURAL_RELATIONSHIPS = [
        'sun' => [
            'friends' => ['moon', 'mars', 'jupiter'],
            'enemies' => ['venus', 'saturn'],
            'neutrals' => ['mercury'],
        ],
        'moon' => [
            'friends' => ['sun', 'mercury'],
            'enemies' => [],
            'neutrals' => ['mars', 'jupiter', 'venus', 'saturn'],
        ],
        'mars' => [
            'friends' => ['sun', 'moon', 'jupiter'],
            'enemies' => ['mercury'],
            'neutrals' => ['venus', 'saturn'],
        ],
        'mercury' => [
            'friends' => ['sun', 'venus'],
            'enemies' => ['moon'],
            'neutrals' => ['mars', 'jupiter', 'saturn'],
        ],
        'jupiter' => [
            'friends' => ['sun', 'moon', 'mars'],
            'enemies' => ['mercury', 'venus'],
            'neutrals' => ['saturn'],
        ],
        'venus' => [
            'friends' => ['mercury', 'saturn'],
            'enemies' => ['sun', 'moon'],
            'neutrals' => ['mars', 'jupiter'],
        ],
        'saturn' => [
            'friends' => ['mercury', 'venus'],
            'enemies' => ['sun', 'moon', 'mars'],
            'neutrals' => ['jupiter'],
        ],
        'rahu' => [
            'friends' => ['mercury', 'venus', 'saturn'],
            'enemies' => ['sun', 'moon', 'mars'],
            'neutrals' => ['jupiter'],
        ],
        'ketu' => [
            'friends' => ['mars', 'venus', 'saturn'],
            'enemies' => ['sun', 'moon'],
            'neutrals' => ['mercury', 'jupiter'],
        ],
    ];

    /**
     * Rashi lordships (0..11).
     */
    public const RASHI_LORDS = [
        0 => 'mars',      // Mesha
        1 => 'venus',     // Vrishabha
        2 => 'mercury',   // Mithuna
        3 => 'moon',      // Karkataka
        4 => 'sun',       // Simha
        5 => 'mercury',   // Kanya
        6 => 'venus',     // Tula
        7 => 'mars',      // Vrishchika
        8 => 'jupiter',   // Dhanu
        9 => 'saturn',    // Makara
        10 => 'saturn',   // Kumbha
        11 => 'jupiter',  // Meena
    ];
}

