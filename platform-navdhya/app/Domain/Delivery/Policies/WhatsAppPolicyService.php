<?php

namespace App\Domain\Delivery\Policies;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * WhatsApp Business API compliance and 24-hour service-window policy (E-CM-04 / Task T-CM04-03).
 */
final class WhatsAppPolicyService
{
    /**
     * @return array{permitted: bool, reason: string}
     */
    public function validateProactiveSend(int|string $userId, string $category, bool $hasOptInConsent): array
    {
        $category = strtoupper(trim($category));

        // Transactional and authentication do not require explicit proactive opt-in under Meta policy
        if (in_array($category, ['TRANSACTIONAL', 'AUTHENTICATION', 'SECURITY'], true)) {
            return ['permitted' => true, 'reason' => 'transactional_exemption'];
        }

        if (! $hasOptInConsent) {
            return ['permitted' => false, 'reason' => 'CONSENT_MISSING'];
        }

        // Check if user has explicitly blocked WhatsApp
        if ($this->isUserBlocked($userId)) {
            return ['permitted' => false, 'reason' => 'USER_BLOCKED_WHATSAPP'];
        }

        return ['permitted' => true, 'reason' => 'consent_granted'];
    }

    public function isInside24HourWindow(?CarbonImmutable $lastUserInboundAt, ?CarbonImmutable $now = null): bool
    {
        if ($lastUserInboundAt === null) {
            return false;
        }

        $now = $now ?? CarbonImmutable::now();

        return $lastUserInboundAt->diffInHours($now) < 24;
    }

    public function recordUserBlock(int|string $userId): void
    {
        Cache::put("comm:wa:blocked:{$userId}", true, 86400 * 365);
    }

    public function isUserBlocked(int|string $userId): bool
    {
        return (bool) Cache::get("comm:wa:blocked:{$userId}", false);
    }

    /**
     * Scenario G: If user blocks WhatsApp, marketing substitution to SMS is strictly prohibited.
     */
    public function canSubstituteMarketingSms(int|string $userId): bool
    {
        if ($this->isUserBlocked($userId)) {
            return false;
        }

        return false; // Anti-spam rule: marketing never substitutes to SMS
    }
}

