<?php

namespace App\Domain\Verification\Issuers;

use App\Domain\Verification\Models\VerificationIssuer;

/**
 * Advisory equivalence mappings and reviewer queue prioritization score Q (§4.3, T-TR03-06).
 *
 * INVARIANT (§4.3): prioritizationScore() is STRICTLY advisory for reviewer queue sorting;
 * it must NEVER be invoked from automated claim approval writers or ClaimManager.
 */
final class QualificationEquivalenceService
{
    /**
     * @return array{equivalent_tier: string, advisory_note: string}
     */
    public function equivalenceAdvisory(CredentialCategory $category, ?VerificationIssuer $issuer = null): array
    {
        $base = match ($category) {
            CredentialCategory::ACHARYA => [
                'equivalent_tier' => 'postgraduate_shastric',
                'advisory_note' => 'Traditional Acharya equates broadly to MA (Sanskrit/Shastra) for reviewer context only.',
            ],
            CredentialCategory::SHASTRI, CredentialCategory::SANSKRIT_UNIVERSITY_DEGREE => [
                'equivalent_tier' => 'undergraduate_shastric',
                'advisory_note' => 'Shastri equates broadly to BA (Sanskrit/Veda); Vidwat Uttama often treated as comparable across southern boards.',
            ],
            CredentialCategory::VIDWAT_PATRA, CredentialCategory::VIDWAT_CERTIFICATE => [
                'equivalent_tier' => 'diploma_or_vidwat',
                'advisory_note' => 'Vidwat Madhyama/Uttama is a recognized state/board examination certificate.',
            ],
            CredentialCategory::VEDA_PATHASHALA,
            CredentialCategory::VEDIC_PATHASHALA_SANAD,
            CredentialCategory::PARAMPARA_GURUKUL_SANAD => [
                'equivalent_tier' => 'traditional_gurukul_sanad',
                'advisory_note' => 'Oral/traditional pathashala completion; evaluate via manual document & sampradaya competency rather than UGC degree equivalence.',
            ],
            default => [
                'equivalent_tier' => 'specialized_ritual_certification',
                'advisory_note' => 'Evaluate within declared ritual/disciplinary scope; do not generalize outside stated track.',
            ],
        };

        if ($issuer !== null && $issuer->equivalence_notes !== null) {
            $base['advisory_note'] .= ' Issuer note: ' . $issuer->equivalence_notes;
        }

        return $base;
    }

    /**
     * Q = w1*I + w2*A + w3*C + w4*E + w5*P for queue ordering only.
     */
    public function prioritizationScore(
        float $issuerStrength,
        float $ageUrgency,
        float $completeness,
        float $evidenceDepth,
        float $priorityBoost = 0.0,
    ): float {
        return round(
            (0.25 * $issuerStrength)
            + (0.20 * $ageUrgency)
            + (0.20 * $completeness)
            + (0.25 * $evidenceDepth)
            + (0.10 * $priorityBoost),
            4,
        );
    }
}

