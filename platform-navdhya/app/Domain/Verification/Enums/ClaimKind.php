<?php

namespace App\Domain\Verification\Enums;

enum ClaimKind: string
{
    case IDENTITY = 'identity';
    case QUALIFICATION = 'qualification';
    case COMPETENCY = 'competency';
    case INSTITUTIONAL_EXISTENCE = 'institutional_existence';
    case INSTITUTIONAL_AUTHORITY = 'institutional_authority';
    case FINANCIAL = 'financial';
    case TAX = 'tax';
    case ANTECEDENT = 'antecedent';
    case ENHANCED_REVIEW = 'enhanced_review';
    case LEGACY = 'legacy';
}
