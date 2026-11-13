<?php

declare(strict_types=1);

namespace App\Modules\Support\Domain\Enums;

use App\Domain\Trust\Parties\PartyEntitlement;

/**
 * Closed support taxonomy (TS-04): ensures harassment, privacy, conduct,
 * additional charges, temple access, prasad, and streaming reports NEVER
 * silently drop into `other`.
 */
enum CaseCategory: string
{
    case BILLING_REFUND = 'billing_refund';
    case BOOKING_SCHEDULE = 'booking_schedule';
    case PUNCTUALITY_NO_SHOW = 'punctuality_no_show';
    case CEREMONY_QUALITY_CHANTING = 'ceremony_quality_chanting';
    case ADDITIONAL_CHARGES_EXTORTION = 'additional_charges_extortion';
    case TEMPLE_ACCESS_DENIAL = 'temple_access_denial';
    case PRASAD_FULFILLMENT = 'prasad_fulfillment';
    case STREAMING_FAILURE = 'streaming_failure';
    case HARASSMENT_SAFETY = 'harassment_safety';
    case PRIVACY_MEDIA_VIOLATION = 'privacy_media_violation';
    case PROVIDER_MISCONDUCT = 'provider_misconduct';
    case ACCOUNT_AUTH = 'account_auth';
    case OTHER = 'other';

    public function isSafetySensitive(): bool
    {
        return in_array($this, [
            self::HARASSMENT_SAFETY,
            self::PRIVACY_MEDIA_VIOLATION,
            self::PROVIDER_MISCONDUCT,
        ], true);
    }

    public function requiredVisibilityScope(CaseType $type): string
    {
        if ($type === CaseType::SAFETY_REPORT || $this->isSafetySensitive()) {
            return PartyEntitlement::SCOPE_RESTRICTED_SAFETY;
        }

        return PartyEntitlement::SCOPE_PARTICIPANT;
    }

    public function defaultUrgency(): string
    {
        if ($this->isSafetySensitive()) {
            return 'CRITICAL';
        }

        return match ($this) {
            self::PUNCTUALITY_NO_SHOW, self::TEMPLE_ACCESS_DENIAL, self::ADDITIONAL_CHARGES_EXTORTION => 'URGENT',
            self::STREAMING_FAILURE, self::BILLING_REFUND => 'HIGH',
            default => 'NORMAL',
        };
    }
}

