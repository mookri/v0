<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Support;

use Illuminate\Support\Facades\Cache;

final readonly class CanonicalHmacCache
{
    /**
     * Compute a deterministic canonical HMAC-SHA256 cache key from input payloads.
     * Prevents cache poisoning, unhashed long strings, and key collisions across tenants.
     *
     * @param string $prefix
     * @param array<string, mixed> $parameters
     * @param string $secret
     * @return string
     */
    public static function generateKey(string $prefix, array $parameters, string $secret = 'navdhya_canonical_cache'): string
    {
        // 1. Sort array keys recursively to ensure deterministic order
        $normalized = self::sortRecursive($parameters);

        // 2. Canonical JSON string without unneeded whitespace
        $json = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        // 3. HMAC-SHA256 signature
        $hmac = hash_hmac('sha256', $json, $secret);

        return "{$prefix}:{$hmac}";
    }

    /**
     * Remember calculation results using canonical HMAC keys.
     *
     * @template T
     * @param string $prefix
     * @param array<string, mixed> $parameters
     * @param int $ttlSeconds
     * @param callable(): T $computeFn
     * @return T
     */
    public static function remember(string $prefix, array $parameters, int $ttlSeconds, callable $computeFn): mixed
    {
        $key = self::generateKey($prefix, $parameters);
        return Cache::remember($key, $ttlSeconds, $computeFn);
    }

    private static function sortRecursive(array $data): array
    {
        ksort($data);
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::sortRecursive($v);
            }
        }
        return $data;
    }
}

