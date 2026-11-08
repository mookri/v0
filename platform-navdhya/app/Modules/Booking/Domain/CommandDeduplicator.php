<?php

namespace App\Modules\Booking\Domain;

use App\Modules\Booking\Domain\Exceptions\IdempotencyConflictException;
use App\Modules\Booking\Models\BookingCommand;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Command Deduplication Engine (Task 1.2.1 & 1.2.4).
 * Guarantees exactly-once command processing per (tenant, actor, verb, key).
 */
final class CommandDeduplicator
{
    /**
     * @param array<string, mixed> $payload
     * @param callable(): array{status: int, data: mixed, target_id?: string|null} $operation
     * @return array{status: int, data: mixed, target_id?: string|null}
     */
    public function execute(
        string $tenantId,
        string $actorId,
        string $verb,
        string $idempotencyKey,
        array $payload,
        ?string $targetId,
        callable $operation
    ): array {
        $canonicalHash = BookingRequestFingerprint::canonicalHash($payload);

        // 1. Check existing command
        $existing = BookingCommand::query()
            ->where('tenant_id', $tenantId)
            ->where('actor_id', $actorId)
            ->where('verb', $verb)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $this->evaluateExisting($existing, $canonicalHash, $targetId);
        }

        // 2. Insert command in PROCESSING state
        $command = new BookingCommand();
        $command->forceFill([
            'tenant_id' => $tenantId,
            'actor_id' => $actorId,
            'verb' => $verb,
            'idempotency_key' => $idempotencyKey,
            'canonical_hash' => $canonicalHash,
            'target_id' => $targetId,
            'outcome_status' => BookingCommand::STATUS_PROCESSING,
            'expires_at' => now()->addHours(24),
        ]);

        try {
            $command->save();
        } catch (UniqueConstraintViolationException) {
            // Race: re-fetch winner
            $winner = BookingCommand::query()
                ->where('tenant_id', $tenantId)
                ->where('actor_id', $actorId)
                ->where('verb', $verb)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($winner !== null) {
                return $this->evaluateExisting($winner, $canonicalHash, $targetId);
            }

            throw new IdempotencyConflictException('Concurrent command execution conflict.');
        }

        // 3. Execute the operation
        try {
            $result = $operation();

            $command->forceFill([
                'outcome_status' => BookingCommand::STATUS_SUCCEEDED,
                'response_payload' => $result['data'] ?? null,
                'http_status' => $result['status'] ?? 200,
                'target_id' => $targetId ?? ($result['target_id'] ?? null),
            ])->save();

            return $result;
        } catch (\Throwable $e) {
            $command->forceFill([
                'outcome_status' => BookingCommand::STATUS_FAILED,
                'response_payload' => ['error' => $e->getMessage()],
                'http_status' => 500,
            ])->save();

            throw $e;
        }
    }

    /**
     * @return array{status: int, data: mixed, target_id?: string|null}
     */
    private function evaluateExisting(BookingCommand $existing, string $canonicalHash, ?string $targetId): array
    {
        if (! hash_equals($existing->canonical_hash, $canonicalHash)) {
            throw new IdempotencyConflictException('Idempotency-Key was already used with a different payload.');
        }

        if ($targetId !== null && $existing->target_id !== null && $existing->target_id !== $targetId) {
            throw new IdempotencyConflictException('Idempotency-Key was already used for a different target.');
        }

        if ($existing->outcome_status === BookingCommand::STATUS_PROCESSING) {
            throw new IdempotencyConflictException('Command is currently being processed. Please retry shortly.');
        }

        return [
            'status' => $existing->http_status ?? 200,
            'data' => $existing->response_payload,
            'target_id' => $existing->target_id,
        ];
    }
}
