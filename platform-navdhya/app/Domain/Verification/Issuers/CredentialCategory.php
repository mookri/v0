<?php

namespace App\Domain\Verification\Issuers;

use App\Domain\Verification\Exceptions\InvalidCredentialCategoryException;

/**
 * Closed qualification taxonomy (T-TR03-02, closes T-19).
 * Rejects free-text credential strings and maps service scope + disclosures.
 */
enum CredentialCategory: string
{
    case VEDA_PATHASHALA = 'veda_pathashala';
    case VEDIC_PATHASHALA_SANAD = 'vedic_pathshala_sanad';
    case VIDWAT_PATRA = 'vidwat_patra';
    case VIDWAT_CERTIFICATE = 'vidwat_certificate';
    case SHASTRI = 'shastri';
    case ACHARYA = 'acharya';
    case VEDIC_RECITATION = 'vedic_recitation';
    case JYOTISHA = 'jyotisha';
    case PANCHANG_JYOTISH_DIPLOMA = 'panchang_jyotish_diploma';
    case KARMAKANDA = 'karmakanda';
    case TEMPLE_AGAMIC_DIKSHA = 'temple_agamic_diksha';
    case SANSKRIT_UNIVERSITY_DEGREE = 'sanskrit_university_degree';
    case PARAMPARA_GURUKUL_SANAD = 'parampara_gurukul_sanad';
    case SHASTRIC_OTHER = 'shastric_other';

    public static function fromInput(string $value): self
    {
        $normalized = strtolower(trim($value));
        $match = self::tryFrom($normalized);
        if ($match === null) {
            throw new InvalidCredentialCategoryException("Unknown credential category [{$value}]. Must belong to closed CredentialCategory taxonomy.");
        }

        return $match;
    }

    /**
     * @return list<string>
     */
    public function unlockedServiceCategories(): array
    {
        return match ($this) {
            self::VEDA_PATHASHALA,
            self::VEDIC_PATHASHALA_SANAD,
            self::VEDIC_RECITATION => ['vedic-parayana', 'homa-havana', 'samhita-recitation'],
            self::VIDWAT_PATRA,
            self::VIDWAT_CERTIFICATE,
            self::SHASTRI,
            self::ACHARYA,
            self::SANSKRIT_UNIVERSITY_DEGREE => ['shastric-consultation', 'karmakanda', 'griha-pravesh', 'vivaha', 'shraddha'],
            self::JYOTISHA,
            self::PANCHANG_JYOTISH_DIPLOMA => ['muhurat-consultation', 'kundali-reading'],
            self::KARMAKANDA => ['karmakanda', 'griha-pravesh', 'satyanarayan-puja', 'rudrabhishek'],
            self::TEMPLE_AGAMIC_DIKSHA => ['agamic-archana', 'festival-utsav', 'kumbhabhishekam'],
            self::PARAMPARA_GURUKUL_SANAD,
            self::SHASTRIC_OTHER => ['general-purohit'],
        };
    }

    public function scopeDisclosure(): ?string
    {
        return match ($this) {
            self::JYOTISHA,
            self::PANCHANG_JYOTISH_DIPLOMA => 'This qualification reflects documented academic or traditional study in Jyotisha/Panchang; it is never an assurance of predictive accuracy or outcome guarantees.',
            default => null,
        };
    }
}

