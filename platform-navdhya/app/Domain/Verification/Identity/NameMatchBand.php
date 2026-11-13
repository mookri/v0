<?php

namespace App\Domain\Verification\Identity;

/**
 * Advisory triage bands only (§9.2 / R-08). NameMatcher never produces automated rejections.
 */
enum NameMatchBand: string
{
    case EXACT_NORMALIZED = 'EXACT_NORMALIZED';
    case MINOR_VARIANT_REVIEW = 'MINOR_VARIANT_REVIEW';
    case SUBSTANTIVE_MISMATCH_ESCALATE = 'SUBSTANTIVE_MISMATCH_ESCALATE';
}

