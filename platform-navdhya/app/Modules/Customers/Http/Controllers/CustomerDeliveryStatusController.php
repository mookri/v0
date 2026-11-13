<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\NotificationDelivery;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Devotee & Staff Delivery Status Surface (E-CM-10 / Task T-CM10-01 / Closes C-16).
 * Surfaces authenticated, tenant-scoped delivery history with recipient masking.
 */
final class CustomerDeliveryStatusController extends Controller
{
    public function __construct(
        private readonly TenantContext $context
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenantId = (string) $this->context->tenantId();

        $query = NotificationDelivery::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId);

        if ($request->filled('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->query('status')));
        }

        $paginator = $query->orderByDesc('created_at')->paginate(20);

        $user = $request->user();
        $items = collect($paginator->items())->map(function (NotificationDelivery $d) use ($user): array {
            $rawRecipient = match ($d->channel) {
                'sms', 'whatsapp' => (string) ($user->phone ?? ''),
                'email' => (string) ($user->email ?? ''),
                default => 'in-app',
            };

            return [
                'id' => $d->id,
                'channel' => $d->channel,
                'status' => $d->status,
                'kind' => $d->kind,
                'recipient_masked' => $this->maskRecipient($rawRecipient),
                'sent_at' => $d->sent_at?->toIso8601String(),
                'delivered_at' => $d->delivered_at?->toIso8601String(),
                'error_code' => $d->error_code ?? $d->error,
                'created_at' => $d->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function maskRecipient(string $recipient): string
    {
        if (empty($recipient)) {
            return '';
        }

        // Email masking: user@domain.com -> u***r@d***n.com
        if (str_contains($recipient, '@')) {
            [$name, $domain] = explode('@', $recipient, 2);
            $maskedName = strlen($name) <= 2
                ? substr($name, 0, 1) . '***'
                : substr($name, 0, 1) . '***' . substr($name, -1);
            return $maskedName . '@' . $domain;
        }

        // Phone masking: +919876543210 -> +91 98****210
        if (strlen($recipient) >= 10) {
            $prefix = substr($recipient, 0, 5);
            $suffix = substr($recipient, -3);
            return "{$prefix}****{$suffix}";
        }

        return '***';
    }
}
