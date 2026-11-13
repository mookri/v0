<?php

namespace App\Domain\Jyotish\Contracts;

/**
 * Classical Zodiac & Nakshatra Constants (T-JY02-05).
 * Pure domain definitions — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
interface ZodiacConstants
{
    public const RASHI_SPAN_DEGREES = 30.0;
    public const NAKSHATRA_SPAN_DEGREES = 13.333333333333334; // 13° 20' = 40/3 degrees
    public const PADA_SPAN_DEGREES = 3.3333333333333335;      // 3° 20' = 10/3 degrees

    public const RASHIS = [
        0 => ['name' => 'Mesha', 'english' => 'Aries', 'lord' => 'mars', 'element' => 'fire', 'modality' => 'chara'],
        1 => ['name' => 'Vrishabha', 'english' => 'Taurus', 'lord' => 'venus', 'element' => 'earth', 'modality' => 'sthira'],
        2 => ['name' => 'Mithuna', 'english' => 'Gemini', 'lord' => 'mercury', 'element' => 'air', 'modality' => 'dwiswabhava'],
        3 => ['name' => 'Karkataka', 'english' => 'Cancer', 'lord' => 'moon', 'element' => 'water', 'modality' => 'chara'],
        4 => ['name' => 'Simha', 'english' => 'Leo', 'lord' => 'sun', 'element' => 'fire', 'modality' => 'sthira'],
        5 => ['name' => 'Kanya', 'english' => 'Virgo', 'lord' => 'mercury', 'element' => 'earth', 'modality' => 'dwiswabhava'],
        6 => ['name' => 'Tula', 'english' => 'Libra', 'lord' => 'venus', 'element' => 'air', 'modality' => 'chara'],
        7 => ['name' => 'Vrishchika', 'english' => 'Scorpio', 'lord' => 'mars', 'element' => 'water', 'modality' => 'sthira'],
        8 => ['name' => 'Dhanu', 'english' => 'Sagittarius', 'lord' => 'jupiter', 'element' => 'fire', 'modality' => 'dwiswabhava'],
        9 => ['name' => 'Makara', 'english' => 'Capricorn', 'lord' => 'saturn', 'element' => 'earth', 'modality' => 'chara'],
        10 => ['name' => 'Kumbha', 'english' => 'Aquarius', 'lord' => 'saturn', 'element' => 'air', 'modality' => 'sthira'],
        11 => ['name' => 'Meena', 'english' => 'Pisces', 'lord' => 'jupiter', 'element' => 'water', 'modality' => 'dwiswabhava'],
    ];

    public const NAKSHATRAS = [
        0 => ['name' => 'Ashwini', 'lord' => 'rahu', 'ruling_graha' => 'ketu', 'syllables' => ['Chu', 'Che', 'Cho', 'La']],
        1 => ['name' => 'Bharani', 'lord' => 'venus', 'ruling_graha' => 'venus', 'syllables' => ['Lee', 'Lu', 'Le', 'Lo']],
        2 => ['name' => 'Krittika', 'lord' => 'sun', 'ruling_graha' => 'sun', 'syllables' => ['A', 'Ee', 'U', 'Ea']],
        3 => ['name' => 'Rohini', 'lord' => 'moon', 'ruling_graha' => 'moon', 'syllables' => ['O', 'Va', 'Vi', 'Vu']],
        4 => ['name' => 'Mrigashira', 'lord' => 'mars', 'ruling_graha' => 'mars', 'syllables' => ['Ve', 'Vo', 'Ka', 'Kee']],
        5 => ['name' => 'Ardra', 'lord' => 'rahu', 'ruling_graha' => 'rahu', 'syllables' => ['Ku', 'Gha', 'Ng', 'Chha']],
        6 => ['name' => 'Punarvasu', 'lord' => 'jupiter', 'ruling_graha' => 'jupiter', 'syllables' => ['Ke', 'Ko', 'Ha', 'Hee']],
        7 => ['name' => 'Pushya', 'lord' => 'saturn', 'ruling_graha' => 'saturn', 'syllables' => ['Hu', 'He', 'Ho', 'Da']],
        8 => ['name' => 'Ashlesha', 'lord' => 'mercury', 'ruling_graha' => 'mercury', 'syllables' => ['Dee', 'Du', 'De', 'Do']],
        9 => ['name' => 'Magha', 'lord' => 'ketu', 'ruling_graha' => 'ketu', 'syllables' => ['Ma', 'Mee', 'Mu', 'Me']],
        10 => ['name' => 'Purva Phalguni', 'lord' => 'venus', 'ruling_graha' => 'venus', 'syllables' => ['Mo', 'Ta', 'Tee', 'Tu']],
        11 => ['name' => 'Uttara Phalguni', 'lord' => 'sun', 'ruling_graha' => 'sun', 'syllables' => ['Te', 'To', 'Pa', 'Pee']],
        12 => ['name' => 'Hasta', 'lord' => 'moon', 'ruling_graha' => 'moon', 'syllables' => ['Pu', 'Sha', 'Na', 'Tha']],
        13 => ['name' => 'Chitra', 'lord' => 'mars', 'ruling_graha' => 'mars', 'syllables' => ['Pe', 'Po', 'Ra', 'Ree']],
        14 => ['name' => 'Swati', 'lord' => 'rahu', 'ruling_graha' => 'rahu', 'syllables' => ['Ru', 'Re', 'Ro', 'Taa']],
        15 => ['name' => 'Vishakha', 'lord' => 'jupiter', 'ruling_graha' => 'jupiter', 'syllables' => ['Tee', 'Too', 'Te', 'To']],
        16 => ['name' => 'Anuradha', 'lord' => 'saturn', 'ruling_graha' => 'saturn', 'syllables' => ['Na', 'Nee', 'Nu', 'Ne']],
        17 => ['name' => 'Jyeshtha', 'lord' => 'mercury', 'ruling_graha' => 'mercury', 'syllables' => ['No', 'Ya', 'Yee', 'Yu']],
        18 => ['name' => 'Mula', 'lord' => 'ketu', 'ruling_graha' => 'ketu', 'syllables' => ['Ye', 'Yo', 'Bha', 'Bhee']],
        19 => ['name' => 'Purva Ashadha', 'lord' => 'venus', 'ruling_graha' => 'venus', 'syllables' => ['Bhu', 'Dha', 'Pha', 'Dhad']],
        20 => ['name' => 'Uttara Ashadha', 'lord' => 'sun', 'ruling_graha' => 'sun', 'syllables' => ['Bhe', 'Bho', 'Ja', 'Jee']],
        21 => ['name' => 'Shravana', 'lord' => 'moon', 'ruling_graha' => 'moon', 'syllables' => ['Khee', 'Khoo', 'Khe', 'Kho']],
        22 => ['name' => 'Dhanishta', 'lord' => 'mars', 'ruling_graha' => 'mars', 'syllables' => ['Ga', 'Gee', 'Gu', 'Ge']],
        23 => ['name' => 'Shatabhisha', 'lord' => 'rahu', 'ruling_graha' => 'rahu', 'syllables' => ['Go', 'Sa', 'See', 'Su']],
        24 => ['name' => 'Purva Bhadrapada', 'lord' => 'jupiter', 'ruling_graha' => 'jupiter', 'syllables' => ['Se', 'So', 'Da', 'Dee']],
        25 => ['name' => 'Uttara Bhadrapada', 'lord' => 'saturn', 'ruling_graha' => 'saturn', 'syllables' => ['Du', 'Tha', 'Jna', 'Da']],
        26 => ['name' => 'Revati', 'lord' => 'mercury', 'ruling_graha' => 'mercury', 'syllables' => ['De', 'Do', 'Cha', 'Chee']],
    ];
}

