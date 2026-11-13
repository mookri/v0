<?php

namespace App\Domain\Delivery\Reliability;

use Illuminate\Support\Facades\Cache;

/**
 * Per-provider circuit breaker (E-CM-03 / Task T-CM03-05).
 * Trips on 5 consecutive provider failures and prevents cascading blocking timeouts.
 */
final class ProviderCircuitBreaker
{
    private int $threshold;
    private int $cooldownSeconds;

    public function __construct(
        ?int $threshold = null,
        ?int $cooldownSeconds = null
    ) {
        $this->threshold = $threshold ?? (int) config('communication.circuit_breaker.failure_threshold', 5);
        $this->cooldownSeconds = $cooldownSeconds ?? (int) config('communication.circuit_breaker.cooldown_seconds', 300);
    }

    public function isOpen(string $provider): bool
    {
        $openUntil = Cache::get($this->openKey($provider));
        if ($openUntil !== null && now()->timestamp < (int) $openUntil) {
            return true;
        }

        return false;
    }

    public function recordSuccess(string $provider): void
    {
        Cache::forget($this->failKey($provider));
        Cache::forget($this->openKey($provider));
    }

    public function recordFailure(string $provider): void
    {
        $count = (int) Cache::get($this->failKey($provider), 0) + 1;
        Cache::put($this->failKey($provider), $count, 600);

        if ($count >= $this->threshold) {
            // Trip circuit breaker
            Cache::put($this->openKey($provider), now()->timestamp + $this->cooldownSeconds, $this->cooldownSeconds);
        }
    }

    private function failKey(string $provider): string
    {
        return "comm:cb:{$provider}:fails";
    }

    private function openKey(string $provider): string
    {
        return "comm:cb:{$provider}:open_until";
    }
}

