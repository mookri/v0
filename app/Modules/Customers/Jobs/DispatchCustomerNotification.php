<?php

namespace App\Modules\Customers\Jobs;

use App\Modules\Customers\Models\CustomerNotification;
use App\Modules\Customers\Models\CustomerSetting;
use App\Modules\Customers\NotificationChannels;
use App\Platform\Features\FeatureFlags;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Customer notification dispatch (N1): the async leg behind
 * NotifiesCustomers::notify() — HTTP enqueues, the worker delivers.
 * Runs on the configured queue connection (database in deployed
 * environments, sync in tests — the same env switch every other
 * queue concern uses).
 *
 * The worker resolves the tenant context itself (clear-then-set,
 * TEN-012 — the OutboxDrainCommand posture) unless an ambient context
 * already matches (inline/sync execution inside a request). Routing
 * (in_app always; preference + quiet-hours policy per
 * packages/communication) is computed here and delivered through the
 * unique (tenant, dedup_key) index — concurrent duplicates collapse.
 */
final class DispatchCustomerNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Spec backoff: min(30_000, 500 * 2 ** attempt). */
    public int $tries = 3;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $userId,
        public readonly string $kind,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $eventId,
    ) {
    }

    public function backoff(): int
    {
        return min(30_000, 500 * 2 ** $this->attempts());
    }

    public function handle(): void
    {
        $context = app(TenantContext::class);
        $ownsContext = ! $context->isResolved();

        if ($ownsContext) {
            $tenant = Tenant::query()->find($this->tenantId);

            if ($tenant === null) {
                return; // tenant gone — nothing to deliver, drop quietly
            }

            $context->clear();
            $context->set(
                $this->tenantId,
                null,
                null,
                (new ConnectionResolver())->cachedFor($tenant),
                ['system' => 'notification-dispatch'],
            );
        }

        try {
            $settings = $this->settings();
            $channels = NotificationChannels::routed(
                $this->kind,
                $settings,
                app(FeatureFlags::class)->enabled('notify.email', $this->tenantId),
                NotificationChannels::quietHoursActive($settings),
            );

            if (in_array(NotificationChannels::CHANNEL_IN_APP, $channels, true)) {
                $this->deliverInApp();
            }

            // Email/sms legs are routed above but executed from N3 when
            // their channels land — N1 delivers the in_app inbox only.
        } finally {
            if ($ownsContext) {
                $context->clear();
            }
        }
    }

    /**
     * In-app delivery: insert under the dedup identity; the unique
     * (tenant, dedup_key) index makes duplicate deliveries idempotent
     * (a raced retry collapses instead of doubling the inbox).
     */
    private function deliverInApp(): void
    {
        $dedupKey = $this->eventId !== null
            ? NotificationChannels::dedupKey($this->kind, $this->eventId, $this->userId, NotificationChannels::CHANNEL_IN_APP)
            : null;

        try {
            $notification = new CustomerNotification();
            $notification->forceFill([
                'user_id' => $this->userId,
                'kind' => $this->kind,
                'category' => NotificationChannels::categoryFor($this->kind),
                'title' => $this->title,
                'body' => $this->body,
                'dedup_key' => $dedupKey,
            ]);
            $notification->save();
        } catch (UniqueConstraintViolationException) {
            // Same event already delivered — dedup is silent.
        }
    }

    /**
     * Stored settings merged over the closed defaults (same resolution
     * the settings controller exposes).
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = CustomerSetting::query()
            ->where('user_id', $this->userId)
            ->pluck('value', 'key')
            ->all();

        return array_merge(CustomerSetting::KEYS, array_intersect_key($stored, CustomerSetting::KEYS));
    }
}
