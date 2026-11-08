<?php

namespace Tests\Modules\Booking;

use App\Modules\Booking\Domain\CommandDeduplicator;
use App\Modules\Booking\Domain\Exceptions\IdempotencyConflictException;
use App\Modules\Booking\Models\BookingCommand;
use Tests\TenantIntegrationTestCase;

/**
 * Command Deduplication Unit Tests (Task 1.2.1, 1.2.4).
 */
final class CommandDeduplicatorTest extends TenantIntegrationTestCase
{
    private CommandDeduplicator $deduplicator;
    private string $tenantId;
    private string $actorId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deduplicator = new CommandDeduplicator();
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $this->tenantId = (string) $tenant->getKey();
        $this->actorId = (string) $user->getAuthIdentifier();
    }

    public function test_first_execution_runs_operation_and_persists_command(): void
    {
        $executed = false;
        $payload = ['sku' => 'PUJA-1', 'time' => '2030-01-01T10:00:00Z'];

        $result = $this->deduplicator->execute(
            $this->tenantId,
            $this->actorId,
            'create',
            'idem-key-1',
            $payload,
            'booking-123',
            function () use (&$executed): array {
                $executed = true;
                return ['status' => 201, 'data' => ['id' => 'booking-123', 'status' => 'DRAFT']];
            }
        );

        $this->assertTrue($executed);
        $this->assertSame(201, $result['status']);
        $this->assertSame('booking-123', $result['data']['id']);

        $command = BookingCommand::query()
            ->where('tenant_id', $this->tenantId)
            ->where('idempotency_key', 'idem-key-1')
            ->first();

        $this->assertNotNull($command);
        $this->assertSame(BookingCommand::STATUS_SUCCEEDED, $command->outcome_status);
        $this->assertSame('booking-123', $command->target_id);
    }

    public function test_replaying_same_command_returns_persisted_result_without_reexecuting(): void
    {
        $executionCount = 0;
        $payload = ['sku' => 'PUJA-1', 'time' => '2030-01-01T10:00:00Z'];

        $first = $this->deduplicator->execute(
            $this->tenantId,
            $this->actorId,
            'create',
            'idem-key-replay',
            $payload,
            'booking-456',
            function () use (&$executionCount): array {
                $executionCount++;
                return ['status' => 201, 'data' => ['id' => 'booking-456']];
            }
        );

        $second = $this->deduplicator->execute(
            $this->tenantId,
            $this->actorId,
            'create',
            'idem-key-replay',
            $payload,
            'booking-456',
            function () use (&$executionCount): array {
                $executionCount++;
                return ['status' => 201, 'data' => ['id' => 'booking-456']];
            }
        );

        $this->assertSame(1, $executionCount, 'Operation closure must only execute once.');
        $this->assertSame($first['data'], $second['data']);
    }

    public function test_same_key_with_different_payload_throws_conflict(): void
    {
        $payload1 = ['sku' => 'PUJA-1', 'time' => '2030-01-01T10:00:00Z'];
        $payload2 = ['sku' => 'PUJA-2', 'time' => '2030-01-01T10:00:00Z'];

        $this->deduplicator->execute(
            $this->tenantId,
            $this->actorId,
            'create',
            'idem-key-conflict',
            $payload1,
            null,
            fn () => ['status' => 201, 'data' => ['id' => 'booking-1']]
        );

        $this->expectException(IdempotencyConflictException::class);
        $this->expectExceptionMessage('Idempotency-Key was already used with a different payload.');

        $this->deduplicator->execute(
            $this->tenantId,
            $this->actorId,
            'create',
            'idem-key-conflict',
            $payload2,
            null,
            fn () => ['status' => 201, 'data' => ['id' => 'booking-2']]
        );
    }

    public function test_same_key_with_different_target_throws_conflict(): void
    {
        $payload = ['reason' => 'user requested'];

        $this->deduplicator->execute(
            $this->tenantId,
            $this->actorId,
            'cancel',
            'idem-key-target',
            $payload,
            'target-booking-A',
            fn () => ['status' => 200, 'data' => ['cancelled' => true]]
        );

        $this->expectException(IdempotencyConflictException::class);
        $this->expectExceptionMessage('Idempotency-Key was already used for a different target.');

        $this->deduplicator->execute(
            $this->tenantId,
            $this->actorId,
            'cancel',
            'idem-key-target',
            $payload,
            'target-booking-B',
            fn () => ['status' => 200, 'data' => ['cancelled' => true]]
        );
    }
}
