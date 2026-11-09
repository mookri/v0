<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Models\CustomerSetting;
use App\Modules\Customers\Models\NotificationPreference;
use App\Platform\Tenancy\TenantContext;

/**
 * PreferencesReader façade (E1 / E4):
 * Evaluates devotee channel and category preferences from the structured
 * notification_preferences matrix, falling back to legacy customer_settings EAV rows.
 */
final class PreferencesReader
{
    public function __construct(
        private readonly TenantContext $context
    ) {
    }

    public function isChannelEnabled(int|string $userId, string $category, string $channel): bool
    {
        $tenantId = (string) $this->context->tenantId();

        $preference = NotificationPreference::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', (int) $userId)
            ->where('category', $category)
            ->where('channel', $channel)
            ->first();

        if ($preference !== null) {
            return $preference->is_enabled;
        }

        // Legacy fallback to customer_settings
        $settingKey = strtolower("notify_{$channel}");
        $legacySetting = CustomerSetting::query()
            ->where('user_id', (string) $userId)
            ->where('key', $settingKey)
            ->first();

        if ($legacySetting !== null) {
            return filter_var($legacySetting->value, FILTER_VALIDATE_BOOLEAN);
        }

        return true;
    }

    /**
     * @return array{start: string|null, end: string|null}
     */
    public function quietHours(int|string $userId): array
    {
        $tenantId = (string) $this->context->tenantId();

        $preference = NotificationPreference::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', (int) $userId)
            ->whereNotNull('quiet_hours_start')
            ->first();

        if ($preference !== null) {
            return [
                'start' => $preference->quiet_hours_start,
                'end' => $preference->quiet_hours_end,
            ];
        }

        $legacyStart = CustomerSetting::query()->where('user_id', (string) $userId)->where('key', 'quiet_hours_start')->first();
        $legacyEnd = CustomerSetting::query()->where('user_id', (string) $userId)->where('key', 'quiet_hours_end')->first();

        return [
            'start' => $legacyStart !== null ? $legacyStart->value : (string) config('notifications.quiet_hours.default_start', '21:00'),
            'end' => $legacyEnd !== null ? $legacyEnd->value : (string) config('notifications.quiet_hours.default_end', '07:00'),
        ];
    }
}
