<?php

namespace App\Domain\Delivery\Planning;

use App\Domain\Delivery\Models\NotificationIntent;
use App\Domain\Delivery\Support\DedupKeyGenerator;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Maps business domain events into unique NotificationIntents (E-CM-03).
 * Rule: exactly one intent is planned per business moment per recipient.
 */
final class IntentPlanner
{
    /**
     * @param array<string, mixed> $payload
     */
    public function plan(
        string $tenantId,
        string $eventType,
        string $recipientRef,
        string $subjectType,
        string $subjectId,
        string $category = 'transactional',
        string $priority = 'normal',
        string $locale = 'en_IN',
        int|string $revision = 1
    ): NotificationIntent {
        $dedupKey = DedupKeyGenerator::generate(
            tenantId: $tenantId,
            eventType: $eventType,
            recipient: $recipientRef,
            subjectEntity: "{$subjectType}:{$subjectId}",
            revision: $revision
        );

        $existing = NotificationIntent::query()
            ->where('tenant_id', $tenantId)
            ->where('dedup_key', $dedupKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            return NotificationIntent::query()->create([
                'tenant_id' => $tenantId,
                'recipient_ref' => $recipientRef,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'category' => $category,
                'priority' => $priority,
                'locale' => $locale,
                'dedup_key' => $dedupKey,
                'policy_version' => '2026.1',
                'status' => 'planned',
            ]);
        } catch (UniqueConstraintViolationException) {
            return NotificationIntent::query()
                ->where('tenant_id', $tenantId)
                ->where('dedup_key', $dedupKey)
                ->firstOrFail();
        }
    }
}

