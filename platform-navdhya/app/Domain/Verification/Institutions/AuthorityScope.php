<?php

namespace App\Domain\Verification\Institutions;

enum AuthorityScope: string
{
    case ONBOARDING = 'onboarding';
    case PROFILE_MANAGEMENT = 'profile_management';
    case BANK_MANDATE = 'bank_mandate';
    case FULL_REPRESENTATION = 'full_representation';

    public function allowsBankMandate(): bool
    {
        return $this === self::BANK_MANDATE || $this === self::FULL_REPRESENTATION;
    }
}

