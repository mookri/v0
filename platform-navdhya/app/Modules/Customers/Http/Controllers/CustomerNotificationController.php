<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\CustomerNotification;
use App\Platform\Api\CursorPagination;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer notifications (B4): newest-first cursor reads plus mark-read
 * (single + read-all). Rows are producer-written (booking transitions
 * B5+); this surface never creates them. Read markers are per-row
 * timestamps; read-all reports how many flipped.
 */
final class CustomerNotificationController extends CustomerSelfServiceController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        [$notifications, $page] = CursorPagination::paginate(
            CustomerNotification::query()->where('user_id', $user->getAuthIdentifier()),
            $request
        );

        return new JsonResponse([
            'data' => array_map(
                static fn (CustomerNotification $notification): array => self::serialize($notification),
                $notifications
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $notification = CustomerNotification::query()
            ->where('id', $id)
            ->where('user_id', (string) $user->getAuthIdentifier())
            ->first();

        if ($notification === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Notification not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))->toDateTimeString(),
            ])->save();
        }

        return new JsonResponse([
            'data' => self::serialize($notification->fresh()),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $read = CustomerNotification::query()
            ->where('user_id', (string) $user->getAuthIdentifier())
            ->whereNull('read_at')
            ->update(['read_at' => CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))->toDateTimeString()]);

        return new JsonResponse([
            'data' => ['object' => 'notification_read_all', 'read' => $read],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(CustomerNotification $notification): array
    {
        return [
            'id' => (string) $notification->getKey(),
            'object' => 'customer_notification',
            'kind' => $notification->kind,
            'category' => $notification->category,
            'title' => $notification->title,
            'body' => $notification->body,
            'read_at' => $notification->read_at,
        ];
    }

    /**
     * Unread count (N4): lightweight endpoint for the badge — no
     * pagination, no body, just the scalar count of unread rows.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $count = CustomerNotification::query()
            ->where('user_id', (string) $user->getAuthIdentifier())
            ->whereNull('read_at')
            ->count();

        return new JsonResponse([
            'data' => ['object' => 'notification_unread_count', 'count' => $count],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
