<?php

namespace App\Domain\Verification\Risk;

/**
 * Nuanced police/legal antecedent status enum (§9.4 / T-TR07-01).
 * Deliberately excludes binary criminal_record booleans.
 */
enum PoliceAntecedentStatus: string
{
    case CLEAR = 'clear';
    case NOT_AVAILABLE = 'not_available';
    case COMPLAINT_FILED = 'complaint_filed';
    case INVESTIGATION_PENDING = 'investigation_pending';
    case ARREST_RECORDED = 'arrest_recorded';
    case CHARGESHEET_FILED = 'chargesheet_filed';
    case DISCHARGED = 'discharged';
    case ACQUITTED = 'acquitted';
    case CONVICTED = 'convicted';

    public function preventsAutoRejection(): bool
    {
        return $this !== self::CONVICTED;
    }

    public function requiresHumanRelevanceReview(): bool
    {
        return in_array($this, [
            self::COMPLAINT_FILED,
            self::INVESTIGATION_PENDING,
            self::ARREST_RECORDED,
            self::CHARGESHEET_FILED,
            self::CONVICTED,
        ], true);
    }
}

