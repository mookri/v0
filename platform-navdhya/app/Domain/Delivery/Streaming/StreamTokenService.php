<?php

namespace App\Domain\Delivery\Streaming;

use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Support\DedupKeyGenerator;
use Carbon\CarbonInterface;

final class StreamTokenService
{
    public const EVENT_STREAM_READY = 'stream.ready';
    public const EVENT_STREAM_LIVE = 'stream.live';

    /**
     * Generate time-bounded, cryptographically signed playback token for devotee.
     */
    public function generateToken(
        string $tenantId,
        string $bookingId,
        int|string $userId,
        CarbonInterface $startsAt,
        CarbonInterface $expiresAt,
        string $signingKey
    ): string {
        $claims = [
            'tenant_id' => $tenantId,
            'booking_id' => $bookingId,
            'user_id' => (string) $userId,
            'nbf' => $startsAt->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'iat' => time(),
        ];

        $encodedClaims = rtrim(strtr(base64_encode(json_encode($claims, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encodedClaims, $signingKey, true);
        $encodedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "{$encodedClaims}.{$encodedSignature}";
    }

    /**
     * Verify token authenticity, validity window, and tenant/user claims.
     *
     * @return array{valid: bool, reason?: string, claims?: array<string, mixed>}
     */
    public function verifyToken(string $token, string $signingKey, ?int $currentTime = null): array
    {
        $now = $currentTime ?? time();
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'malformed_token'];
        }

        [$encodedClaims, $encodedSig] = $parts;

        $expectedSig = hash_hmac('sha256', $encodedClaims, $signingKey, true);
        $expectedEncodedSig = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

        if (! hash_equals($expectedEncodedSig, $encodedSig)) {
            return ['valid' => false, 'reason' => 'invalid_signature'];
        }

        $claimsJson = base64_decode(strtr($encodedClaims, '-_', '+/'), true);
        if ($claimsJson === false) {
            return ['valid' => false, 'reason' => 'invalid_payload_encoding'];
        }

        try {
            /** @var array<string, mixed> $claims */
            $claims = json_decode($claimsJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['valid' => false, 'reason' => 'invalid_json'];
        }

        if (isset($claims['nbf']) && $now < (int) $claims['nbf']) {
            return ['valid' => false, 'reason' => 'token_not_yet_valid', 'claims' => $claims];
        }

        if (isset($claims['exp']) && $now > (int) $claims['exp']) {
            return ['valid' => false, 'reason' => 'token_expired', 'claims' => $claims];
        }

        return ['valid' => true, 'claims' => $claims];
    }

    /**
     * Enqueue stream access notification in outbox with secure watch URL.
     */
    public function enqueueStreamAccessNotification(
        string $tenantId,
        string $bookingId,
        int|string $userId,
        string $streamToken,
        string $baseUrl = 'https://app.navdhya.com/live'
    ): NotificationOutbox {
        $watchUrl = "{$baseUrl}/{$bookingId}?token={$streamToken}";

        $dedupKey = DedupKeyGenerator::generate(
            tenantId: $tenantId,
            eventType: self::EVENT_STREAM_LIVE,
            recipient: (string) $userId,
            subjectEntity: "booking:{$bookingId}:stream",
            revision: 1
        );

        return NotificationOutbox::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => "evt_stream_{$bookingId}_{$userId}",
            'aggregate_type' => 'booking_stream',
            'aggregate_id' => $bookingId,
            'event_type' => self::EVENT_STREAM_LIVE,
            'payload_json' => [
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'template_kind' => 'stream_live_access',
                'watch_url' => $watchUrl,
                'dedup_key' => $dedupKey,
            ],
            'status' => NotificationOutbox::STATUS_PENDING,
            'attempt_count' => 0,
        ]);
    }
}

