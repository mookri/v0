<?php

namespace App\Platform\Identity\Otp;

use Carbon\CarbonImmutable;

/**
 * One place owns every delivery constant: challenge TTL, attempt cap,
 * the compounding resend schedule (delays are measured from the
 * previous send — 60s to the 1st resend, 180s to the 2nd, 540s to the
 * 3rd), the fresh-challenge back-off and its rolling hourly cap.
 * Backed by config/otp-policy.php `delivery`; environments tune policy
 * here, never in code. Resend channel codes are provider dialect (the
 * client passes them straight to the widget's retryOtp) so they ride
 * the provider's own config block (services.msg91).
 */
final readonly class OtpDeliveryPolicy
{
    /**
     * @param  list<int>  $resendDelays
     * @param  list<string>  $resendChannels
     */
    public function __construct(
        public int $ttlSeconds,
        public int $maxAttempts,
        public int $resendMax,
        public array $resendDelays,
        public int $freshRetryAfterSeconds,
        public int $freshMaxPerHour,
        public array $resendChannels,
    ) {
    }

    public static function load(): self
    {
        $delivery = (array) config('otp-policy.delivery', []);

        $delays = array_values(array_filter(
            array_map('intval', (array) ($delivery['resend_delays'] ?? [60, 180, 540])),
            static fn (int $seconds): bool => $seconds > 0,
        ));

        $channels = array_values(array_filter(
            array_map('strval', (array) config('services.msg91.resend_channels', ['11'])),
            static fn (string $channel): bool => $channel !== '',
        ));

        return new self(
            ttlSeconds: max(1, (int) ($delivery['ttl'] ?? 1200)),
            maxAttempts: max(1, (int) ($delivery['max_attempts'] ?? 3)),
            resendMax: (int) ($delivery['resend_max'] ?? 3),
            resendDelays: $delays,
            freshRetryAfterSeconds: max(1, (int) ($delivery['fresh_retry_after'] ?? 600)),
            freshMaxPerHour: max(1, (int) ($delivery['fresh_max_per_hour'] ?? 3)),
            resendChannels: $channels === [] ? ['11'] : $channels,
        );
    }

    /**
     * Wait before resend #resendCount + 1 (resendCount is the number of
     * resends already spent); null when the schedule is exhausted.
     */
    public function nextResendAllowedAt(CarbonImmutable $lastSentAt, int $resendCount): ?CarbonImmutable
    {
        $delay = $this->resendDelays[$resendCount] ?? null;

        return $delay === null ? null : $lastSentAt->addSeconds($delay);
    }

    /**
     * The channel code for resend #resendCount + 1; the provider list
     * rotates (SMS first, then voice, ...) and repeats if longer
     * schedules are ever configured.
     */
    public function channelFor(int $resendIndex): string
    {
        $channels = $this->resendChannels;

        return $channels[$resendIndex % count($channels)];
    }
}
