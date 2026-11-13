<?php

namespace App\Domain\Verification\Risk;

use App\Domain\Verification\Exceptions\RegulatoryGateLockedException;
use Illuminate\Support\Facades\DB;

/**
 * Counsel Legal Applicability Gate (G-LEGAL-0 / A-1 / §9.5 / §20 R-07).
 * Fail-closed: sanctions, PEP, and STR paths cannot execute unless counsel classification
 * explicitly marks the platform as a classified_reporting_entity AND aml_features_enabled is true.
 */
final class RegulatoryApplicabilityGate
{
    public const GATE_KEY = 'pmla_fiu_reporting_entity';

    public function isAmlEnabled(): bool
    {
        $row = DB::connection('central')
            ->table('verification_regulatory_gates')
            ->where('gate_key', self::GATE_KEY)
            ->first();

        if ($row === null) {
            return false;
        }

        return $row->classification_status === 'classified_reporting_entity'
            && (bool) $row->aml_features_enabled === true
            && is_string($row->counsel_opinion_ref)
            && trim($row->counsel_opinion_ref) !== ''
            && $row->signed_at !== null;
    }

    public function assertAmlEnabled(): void
    {
        if (! $this->isAmlEnabled()) {
            throw new RegulatoryGateLockedException(
                'AML/Sanctions/PEP screening is locked behind G-LEGAL-0 until signed counsel PMLA classification clears.',
            );
        }
    }
}

