<?php

namespace App\Domain\Verification\Institutions;

enum InstitutionalLegalForm: string
{
    case PUBLIC_RELIGIOUS_TRUST = 'public_religious_trust';
    case REGISTERED_SOCIETY = 'registered_society';
    case SECTION_8_COMPANY = 'section_8_company';
    case STATE_ENDOWMENT_BOARD = 'state_endowment_board';
    case STATUTORY_DEVASWOM_BOARD = 'statutory_devaswom_board';
    case UNREGISTERED_INSTITUTION = 'unregistered_institution';

    public function requiresEnhancedUnregisteredChecklist(): bool
    {
        return $this === self::UNREGISTERED_INSTITUTION;
    }
}

