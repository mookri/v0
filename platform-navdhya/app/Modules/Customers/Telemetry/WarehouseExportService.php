<?php

namespace App\Modules\Customers\Telemetry;

use App\Modules\Customers\Models\EventsExportCursor;
use App\Modules\Customers\Models\UserEvent;
use Illuminate\Support\Collection;

/**
 * Warehouse signal export service (E5):
 * Exports user_events into normalized SignalEnvelope payloads using ULID cursor pagination.
 * Hard-excludes all special-category spiritual data (Gotra, Nakshatra, Rashi, Kuladevata, family lineage).
 */
final class WarehouseExportService
{
    public const MAX_PARAMS = 25;
    public const RESERVED_SOURCE = 'server';

    private string $hmacSecret;

    public function __construct(?string $hmacSecret = null)
    {
        $this->hmacSecret = $hmacSecret ?? (string) config('app.key', 'navdhya_telemetry_salt');
    }

    /**
     * @return array{envelopes: list<array<string, mixed>>, next_cursor: string|null}
     */
    public function exportBatch(string $feedName = 'analytics_warehouse', int $limit = 100): array
    {
        $cursor = EventsExportCursor::query()->find($feedName);
        $lastUlid = $cursor?->last_processed_ulid;

        $query = UserEvent::query()->withoutGlobalScopes()->orderBy('id')->limit($limit);

        if ($lastUlid !== null) {
            $query->where('id', '>', $lastUlid);
        }

        /** @var Collection<int, UserEvent> $events */
        $events = $query->get();
        if ($events->isEmpty()) {
            return [
                'envelopes' => [],
                'next_cursor' => $lastUlid,
            ];
        }

        $envelopes = [];
        $newLastUlid = $lastUlid;

        foreach ($events as $event) {
            $envelopes[] = $this->transformToSignalEnvelope($event);
            $newLastUlid = (string) $event->id;
        }

        EventsExportCursor::query()->updateOrCreate(
            ['feed_name' => $feedName],
            ['last_processed_ulid' => $newLastUlid, 'updated_at' => now()]
        );

        return [
            'envelopes' => $envelopes,
            'next_cursor' => $newLastUlid,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transformToSignalEnvelope(UserEvent $event): array
    {
        // 1. Pseudonymize user_id with HMAC-SHA256
        $uidHash = $event->user_id !== null
            ? hash_hmac('sha256', (string) $event->user_id, $this->hmacSecret)
            : null;

        // 2. Filter properties against strict primitive allowlist (<= 25 items)
        $cleanParams = [];
        $bannedKeys = [
            'gotra', 'nakshatra', 'rashi', 'kuladevata', 'shraddha',
            'family_member', 'birth_date', 'death_date', 'phone', 'email', 'name',
        ];

        foreach ($event->properties as $k => $v) {
            $lowerKey = strtolower((string) $k);

            // Special-category exclusion guard
            $isBanned = false;
            foreach ($bannedKeys as $banned) {
                if (str_contains($lowerKey, $banned)) {
                    $isBanned = true;
                    break;
                }
            }

            if ($isBanned) {
                continue;
            }

            if (is_scalar($v) && count($cleanParams) < self::MAX_PARAMS) {
                // Coarse-export truncates category_path to root segment for BigQuery privacy (F5)
                if ($lowerKey === 'category_path' && is_string($v)) {
                    $segments = array_values(array_filter(explode('/', trim($v, '/'))));
                    $cleanParams[$k] = ! empty($segments) ? '/' . $segments[0] . '/' : '/';
                } else {
                    $cleanParams[$k] = $v;
                }
            }
        }

        // 3. Format SignalEnvelope
        return [
            'id' => (string) $event->id,
            'event_type' => $event->event_type,
            'occurred_at' => $event->occurred_at->toIso8601String(),
            'source' => self::RESERVED_SOURCE,
            'actor' => [
                'uid_hash' => $uidHash,
            ],
            'route_template' => (string) ($event->context['route_template'] ?? '/app/events'),
            'params' => $cleanParams,
        ];
    }
}

