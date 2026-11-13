<?php

namespace App\Domain\Privacy\Enums;

/**
 * Closed non-cascading consent purposes (T-17 / E-TR-06).
 * No omnibus/wildcard consent is permitted.
 */
enum ConsentPurpose: string
{
    case IDENTITY_KYC = 'identity_kyc';
    case QUALIFICATION_OUTREACH = 'qualification_outreach';
    case ANTECEDENT_CHECK = 'antecedent_check';
    case FINANCIAL_PAYOUT = 'financial_payout';
    case PUBLIC_BADGE_DISPLAY = 'public_badge_display';
    case MARKETING_COMMS = 'marketing_comms';
}

