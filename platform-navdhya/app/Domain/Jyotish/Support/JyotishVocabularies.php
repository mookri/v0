<?php

namespace App\Domain\Jyotish\Support;

/**
 * Pure domain canonical Jyotish vocabularies (T-JY00-14).
 * Mirrors packages/validation/src/constants/jyotish.ts verbatim.
 * Pure domain model — zero framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class JyotishVocabularies
{
    /**
     * @var list<string>
     */
    public const CANONICAL_AYANAMSA = [
        'LAHIRI',
        'RAMAN',
        'KRISHNAMURTI',
        'YUKTESHWAR',
        'TRUE_CHITRA',
        'FAGAN_BRADLEY',
        'SURYA_SIDDHANTA',
    ];

    /**
     * @var list<string>
     */
    public const CANONICAL_HOUSE_SYSTEMS = [
        'WHOLE_SIGN',
        'PLACIDUS',
        'SRIPATI',
        'EQUAL_HOUSE',
        'KOCH',
        'CAMPANUS',
        'REGIOMONTANUS',
    ];

    /**
     * @var list<string>
     */
    public const CANONICAL_VARGAS = [
        'D1',
        'D2',
        'D3',
        'D4',
        'D7',
        'D9',
        'D10',
        'D12',
        'D16',
        'D20',
        'D24',
        'D27',
        'D30',
        'D40',
        'D45',
        'D60',
    ];

    /**
     * @var list<string>
     */
    public const CANONICAL_DASHA_SYSTEMS = [
        'VIMSHOTTARI',
        'ASHTOTTARI',
        'YOGINI',
        'CHARA',
        'KALACHAKRA',
    ];
}

