<?php

namespace App\Domain\Delivery\Policies;

use App\Modules\Booking\Models\Booking;

/**
 * Recipient entitlement and spiritual data-minimization guard (E-CM-08 / Task T-CM08-02 / Gate G-PRIV-1).
 * Verifies recipient authorization and strictly scrubs special-category spiritual attributes
 * (gotra, nakshatra, date of birth) from external outbound message payloads.
 */
final class RecipientEntitlementGuard
{
    private const SENSITIVE_SPIRITUAL_FIELDS = [
        'gotra',
        'nakshatra',
        'date_of_birth',
        'dob',
        'rashi',
        'kundali',
        'birth_time',
        'birth_place',
    ];

    public function isAuthorized(string $tenantId, int|string $userId, string $subjectType, string $subjectId): bool
    {
        if (str_contains(strtolower($subjectType), 'booking')) {
            $booking = Booking::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $subjectId)
                ->first();

            if ($booking === null) {
                return false;
            }

            return (string) $booking->user_id === (string) $userId;
        }

        return true;
    }

    /**
     * Data minimization: scrubs sensitive spiritual PII from outbound template variables.
     *
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    public function sanitizeVariables(array $variables): array
    {
        $sanitized = [];

        foreach ($variables as $key => $value) {
            $lowerKey = strtolower(trim((string) $key));
            if (in_array($lowerKey, self::SENSITIVE_SPIRITUAL_FIELDS, true)) {
                continue; // Omit sensitive spiritual attribute
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeVariables($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}

