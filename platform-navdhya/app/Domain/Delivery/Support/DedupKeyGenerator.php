<?php

namespace App\Domain\Delivery\Support;

/**
 * Deterministic deduplication key generator for notification intents and channel deliveries (C-10).
 * Rule: sha256(tenant_id : event_type : recipient : subject_entity : revision)
 */
final class DedupKeyGenerator
{
    public static function generate(
        string $tenantId,
        string $eventType,
        string $recipient,
        string $subjectEntity,
        string|int $revision = 1
    ): string {
        $tenantId = trim($tenantId);
        $eventType = strtolower(trim($eventType));
        $recipient = trim($recipient);
        $subjectEntity = trim($subjectEntity);
        $revision = (string) $revision;

        return hash('sha256', "{$tenantId}:{$eventType}:{$recipient}:{$subjectEntity}:{$revision}");
    }
}

