<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Streaming\StreamTokenService;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM09-03:
 * Time-bounded, cryptographically signed streaming tokens and notification enqueuing.
 */
final class StreamingAccessJourneyTest extends TenantIntegrationTestCase
{
    private string $signingKey = 'super_secret_stream_signing_key_32_bytes_len!';

    public function test_stream_token_generation_and_valid_verification(): void
    {
        $service = new StreamTokenService();
        $now = CarbonImmutable::now();
        $startsAt = $now->subMinutes(10);
        $expiresAt = $now->addHours(2);

        $token = $service->generateToken(
            tenantId: 'tenant_live_01',
            bookingId: 'book_stream_100',
            userId: 777,
            startsAt: $startsAt,
            expiresAt: $expiresAt,
            signingKey: $this->signingKey
        );

        $this->assertNotEmpty($token);

        $result = $service->verifyToken($token, $this->signingKey);
        $this->assertTrue($result['valid']);
        $this->assertSame('tenant_live_01', $result['claims']['tenant_id']);
        $this->assertSame('book_stream_100', $result['claims']['booking_id']);
        $this->assertSame('777', $result['claims']['user_id']);
    }

    public function test_stream_token_fails_on_forged_signature(): void
    {
        $service = new StreamTokenService();
        $now = CarbonImmutable::now();

        $token = $service->generateToken(
            tenantId: 'tenant_live_01',
            bookingId: 'book_stream_100',
            userId: 777,
            startsAt: $now->subMinutes(5),
            expiresAt: $now->addHours(1),
            signingKey: $this->signingKey
        );

        // Verify with wrong key
        $result = $service->verifyToken($token, 'wrong_key');
        $this->assertFalse($result['valid']);
        $this->assertSame('invalid_signature', $result['reason']);
    }

    public function test_stream_token_enforces_nbf_and_exp_boundaries(): void
    {
        $service = new StreamTokenService();
        $now = CarbonImmutable::now();

        // 1. Not yet valid (starts in future)
        $futureToken = $service->generateToken(
            tenantId: 'tenant_live_01',
            bookingId: 'book_stream_100',
            userId: 777,
            startsAt: $now->addMinutes(30),
            expiresAt: $now->addHours(2),
            signingKey: $this->signingKey
        );
        $nbfResult = $service->verifyToken($futureToken, $this->signingKey, $now->getTimestamp());
        $this->assertFalse($nbfResult['valid']);
        $this->assertSame('token_not_yet_valid', $nbfResult['reason']);

        // 2. Expired (expired 10 minutes ago)
        $expiredToken = $service->generateToken(
            tenantId: 'tenant_live_01',
            bookingId: 'book_stream_100',
            userId: 777,
            startsAt: $now->subHours(2),
            expiresAt: $now->subMinutes(10),
            signingKey: $this->signingKey
        );
        $expResult = $service->verifyToken($expiredToken, $this->signingKey, $now->getTimestamp());
        $this->assertFalse($expResult['valid']);
        $this->assertSame('token_expired', $expResult['reason']);
    }

    public function test_enqueue_stream_access_notification_creates_outbox_record(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $service = new StreamTokenService();
        $now = CarbonImmutable::now();
        $token = $service->generateToken(
            tenantId: $tenantId,
            bookingId: 'book_stream_999',
            userId: 888,
            startsAt: $now->subMinutes(5),
            expiresAt: $now->addHours(1),
            signingKey: $this->signingKey
        );

        $outbox = $service->enqueueStreamAccessNotification(
            tenantId: $tenantId,
            bookingId: 'book_stream_999',
            userId: 888,
            streamToken: $token
        );

        $this->assertInstanceOf(NotificationOutbox::class, $outbox);
        $this->assertSame(StreamTokenService::EVENT_STREAM_LIVE, $outbox->event_type);
        $this->assertStringContainsString($token, $outbox->payload_json['watch_url']);
        $this->assertSame('stream_live_access', $outbox->payload_json['template_kind']);
    }
}

