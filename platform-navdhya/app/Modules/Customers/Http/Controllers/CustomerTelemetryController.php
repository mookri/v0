<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\UserEvent;
use App\Modules\Customers\Telemetry\EventRegistry;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer telemetry ingestion endpoint (E5).
 * Validated against EventRegistry, consent-gated by ANALYTICS purpose,
 * and property payload strictly capped at 2,048 bytes per event.
 */
final class CustomerTelemetryController
{
    public const MAX_BATCH_SIZE = 50;
    public const MAX_PROPERTIES_BYTES = 2048;

    public function __construct(
        private readonly TenantContext $context
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user ? (int) $user->getAuthIdentifier() : null;
        $tenantId = (string) $this->context->tenantId();

        $data = $request->validate([
            'events' => ['required', 'array', 'max:' . self::MAX_BATCH_SIZE],
            'events.*.event_type' => ['required', 'string'],
            'events.*.properties' => ['required', 'array'],
            'events.*.context' => ['sometimes', 'nullable', 'array'],
            'events.*.occurred_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $events = $data['events'];

        // Validate each event against registry and size limits
        foreach ($events as $index => $item) {
            if (! EventRegistry::isValid($item['event_type'])) {
                return new JsonResponse([
                    'error' => [
                        'code' => 'invalid_event_type',
                        'message' => "Event '{$item['event_type']}' at index {$index} is not registered in the baseline schema.",
                    ],
                ], 422);
            }

            $encoded = json_encode($item['properties']);
            if ($encoded === false || strlen($encoded) > self::MAX_PROPERTIES_BYTES) {
                return new JsonResponse([
                    'error' => [
                        'code' => 'payload_too_large',
                        'message' => "Properties for event at index {$index} exceed the 2,048-byte limit.",
                    ],
                ], 422);
            }
        }

        // Check ANALYTICS consent for identified user
        $hasAnalyticsConsent = false;
        if ($userId !== null) {
            $latestConsent = ConsentLedger::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('purpose', ConsentLedger::PURPOSE_ANALYTICS)
                ->orderByDesc('created_at')
                ->first();

            $hasAnalyticsConsent = ($latestConsent !== null && $latestConsent->state === ConsentLedger::STATE_GRANTED);
        }

        // If consent is not granted, drop events immediately without storing (GDPR/DPDP compliance)
        if (! $hasAnalyticsConsent) {
            return new JsonResponse([
                'status' => 'accepted',
                'stored' => 0,
                'dropped' => count($events),
                'reason' => 'analytics_consent_absent',
            ], 202);
        }

        $now = CarbonImmutable::now('UTC');
        $storedCount = 0;

        foreach ($events as $item) {
            $occurredAt = isset($item['occurred_at'])
                ? CarbonImmutable::parse($item['occurred_at'])
                : $now;

            UserEvent::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'event_type' => $item['event_type'],
                'properties' => $item['properties'],
                'context' => $item['context'] ?? null,
                'source' => UserEvent::SOURCE_API,
                'purpose' => 'ANALYTICS',
                'occurred_at' => $occurredAt,
            ]);

            $storedCount++;
        }

        return new JsonResponse([
            'status' => 'accepted',
            'stored' => $storedCount,
            'dropped' => 0,
        ], 202);
    }
}
