<?php

namespace App\Modules\Booking\Domain;

use Carbon\CarbonImmutable;

final class BookingRequestFingerprint
{
    public const VERSION = 2;

    /**
     * @param array<string, mixed> $input
     */
    public static function hash(array $input): string
    {
        $fields = [
            'service_sku' => $input['service_sku'],
            'scheduled_at' => CarbonImmutable::parse($input['scheduled_at'])->utc()->format('Y-m-d\TH:i:s.u\Z'),
            'duration_minutes' => (int) ($input['duration_minutes'] ?? 60),
            'address_id' => $input['address_id'] ?? null,
            'address_snapshot' => $input['address_snapshot'] ?? null,
        ];

        if (array_key_exists('selected_pandit_id', $input)) {
            $fields['selected_pandit_id'] = $input['selected_pandit_id'];
        }
        if (array_key_exists('selected_temple_id', $input)) {
            $fields['selected_temple_id'] = $input['selected_temple_id'];
        }
        if (array_key_exists('quote_id', $input)) {
            $fields['quote_id'] = $input['quote_id'];
        }

        return self::canonicalHash($fields);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $storedAddress
     */
    public static function matches(array $input, ?string $hash, int $version, ?array $storedAddress): bool
    {
        if ($hash === null) {
            return false;
        }

        if ($version === self::VERSION) {
            return hash_equals($hash, self::hash($input));
        }

        if ($version !== 1) {
            return false;
        }

        // Legacy hashes omitted the address; compare its retained snapshot before replay.
        $legacyHash = hash('sha256', json_encode([
            'service_sku' => $input['service_sku'],
            'scheduled_at' => $input['scheduled_at'],
            'duration_minutes' => $input['duration_minutes'] ?? null,
            'address_id' => $input['address_id'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return hash_equals($hash, $legacyHash)
            && hash_equals(self::canonicalHash($storedAddress), self::canonicalHash($input['address_snapshot'] ?? null));
    }

    public static function canonicalHash(mixed $value): string
    {
        return hash('sha256', json_encode(self::ordered($value), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private static function ordered(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map(self::ordered(...), $value);
    }
}
