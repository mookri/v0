<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Models\ConsentLedger;
use App\Platform\Tenancy\TenantContext;

/**
 * Dispatch consent & preference gate (E4):
 * Evaluates devotee consent against required purpose and channel preference matrix.
 * Enforces privacy guarantees before any third-party provider dispatch occurs.
 */
final class NotificationConsentGateService
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly PreferencesReader $preferencesReader
    ) {
    }

    /**
     * @return array{permitted: bool, reason: string}
     */
    public function canDispatch(int|string $userId, string $category, string $channel): array
    {
        $tenantId = (string) $this->context->tenantId();
        $normalizedCategory = strtoupper($category);
        $normalizedChannel = strtoupper($channel);

        // 1. Transactional / Security communications always permitted by law & contract
        if (in_array($normalizedCategory, ['ACCOUNT', 'BOOKING', 'SECURITY', 'TRANSACTIONAL'], true)) {
            return ['permitted' => true, 'reason' => 'transactional_exemption'];
        }

        // 2. Map Category to Required Consent Purpose
        $requiredPurpose = match ($normalizedCategory) {
            'REMINDER', 'PANCHANG_ALERT', 'RITUAL' => ConsentLedger::PURPOSE_RITUAL_REMINDERS,
            'MARKETING', 'OFFERS', 'PROMOTIONS' => ConsentLedger::PURPOSE_MARKETING,
            default => ConsentLedger::PURPOSE_TRANSACTIONAL,
        };

        // 3. Evaluate Active Consent in consent_ledger (most recent record for purpose)
        $latestConsent = ConsentLedger::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', (int) $userId)
            ->where('purpose', $requiredPurpose)
            ->orderByDesc('id')
            ->first();

        if ($latestConsent === null || $latestConsent->state !== ConsentLedger::STATE_GRANTED) {
            return [
                'permitted' => false,
                'reason' => "consent_missing_or_withdrawn_for_{$requiredPurpose}",
            ];
        }

        // 4. Evaluate Channel Preference Matrix
        $channelEnabled = $this->preferencesReader->isChannelEnabled($userId, $normalizedCategory, $normalizedChannel);
        if (! $channelEnabled) {
            return [
                'permitted' => false,
                'reason' => "channel_{$normalizedChannel}_disabled_by_user_preference",
            ];
        }

        return ['permitted' => true, 'reason' => 'consent_and_preference_granted'];
    }
}
