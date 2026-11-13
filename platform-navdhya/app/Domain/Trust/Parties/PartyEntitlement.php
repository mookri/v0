<?php

declare(strict_types=1);

namespace App\Domain\Trust\Parties;

/**
 * Visibility & disclosure entitlement boundaries across cases, reviews, and incidents (§14.4, R-08).
 */
final readonly class PartyEntitlement
{
    public const SCOPE_PUBLIC = 'PUBLIC';

    public const SCOPE_PARTICIPANT = 'PARTICIPANT';

    public const SCOPE_PROVIDER_REDACTED = 'PROVIDER_REDACTED';

    public const SCOPE_REPORTER_PRIVATE = 'REPORTER_PRIVATE';

    public const SCOPE_INTERNAL_STAFF = 'INTERNAL_STAFF';

    public const SCOPE_RESTRICTED_SAFETY = 'RESTRICTED_SAFETY';

    public static function canViewScope(PartyRole $viewerRole, string $visibilityScope, bool $hasSafetyCapability = false): bool
    {
        return match ($visibilityScope) {
            self::SCOPE_PUBLIC => true,
            self::SCOPE_PARTICIPANT => in_array($viewerRole, [PartyRole::DEVOTEE, PartyRole::REPRESENTATIVE, PartyRole::PLATFORM], true),
            self::SCOPE_PROVIDER_REDACTED => in_array($viewerRole, [PartyRole::PROVIDER, PartyRole::INSTITUTION, PartyRole::PLATFORM], true),
            self::SCOPE_REPORTER_PRIVATE => in_array($viewerRole, [PartyRole::DEVOTEE, PartyRole::REPRESENTATIVE], true)
                || ($viewerRole === PartyRole::PLATFORM && $hasSafetyCapability),
            self::SCOPE_INTERNAL_STAFF => $viewerRole === PartyRole::PLATFORM,
            self::SCOPE_RESTRICTED_SAFETY => $viewerRole === PartyRole::PLATFORM && $hasSafetyCapability,
            default => false,
        };
    }
}

