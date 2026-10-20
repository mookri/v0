<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Modules\Customers\Jobs\DispatchCustomerNotification;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerNotification;
use App\Modules\Customers\Models\CustomerSetting;
use App\Modules\Customers\NotificationChannels;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Tests\TenantIntegrationTestCase;

/**
 * N1 notification dispatcher: notify() enqueues (database queue in
 * deployed environments, sync inline in tests), the worker resolves
 * its own tenant, dedup collapses replays under the unique (tenant,
 * dedup_key) index, and the routing matrix (in_app always; preference
 * + quiet-hours policy per packages/communication) is enforced.
 */
final class NotificationDispatchTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);

        try {
            BuyerProfile::factory()->create(['user_id' => $user->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user];
    }

    public function test_notify_delivers_in_app_inline_on_the_sync_queue(): void
    {
        [$tenant, $user] = $this->customer();
        $tenantId = (string) $tenant->getKey();
        $userId = (string) $user->getAuthIdentifier();

        $this->resolveContext($tenant);

        try {
            app(NotifiesCustomers::class)->notify(
                $tenantId,
                $userId,
                'booking',
                'Booking confirmed',
                'Your puja is confirmed.',
            );
        } finally {
            $this->clearContext();
        }

        $notification = CustomerNotification::query()
            ->withoutGlobalScopes()
            ->where('user_id', $userId)
            ->sole();

        $this->assertSame('Booking confirmed', $notification->title);
        $this->assertSame('transactional', $notification->category);
        $this->assertNull($notification->dedup_key);
    }

    public function test_notify_enqueues_the_dispatch_job(): void
    {
        [$tenant, $user] = $this->customer();
        Queue::fake();

        $this->resolveContext($tenant);

        try {
            app(NotifiesCustomers::class)->notify(
                (string) $tenant->getKey(),
                (string) $user->getAuthIdentifier(),
                'booking',
                'Enqueued only',
            );
        } finally {
            $this->clearContext();
        }

        Queue::assertPushed(DispatchCustomerNotification::class, 1);
        $this->assertSame(0, CustomerNotification::query()->withoutGlobalScopes()->count());
    }

    public function test_dedup_collapses_repeated_deliveries_of_the_same_event(): void
    {
        [$tenant, $user] = $this->customer();
        $tenantId = (string) $tenant->getKey();
        $userId = (string) $user->getAuthIdentifier();

        $this->resolveContext($tenant);

        try {
            $notifier = app(NotifiesCustomers::class);
            $notifier->notify($tenantId, $userId, 'booking', 'Same event', null, 'evt_001');
            $notifier->notify($tenantId, $userId, 'booking', 'Same event', null, 'evt_001');
            $notifier->notify($tenantId, $userId, 'booking', 'Other event', null, 'evt_002');
        } finally {
            $this->clearContext();
        }

        $rows = CustomerNotification::query()
            ->withoutGlobalScopes()
            ->where('user_id', $userId)
            ->orderBy('dedup_key')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame(
            ['booking:evt_001:'.$userId.':in_app', 'booking:evt_002:'.$userId.':in_app'],
            $rows->pluck('dedup_key')->all(),
        );
    }

    public function test_notify_fails_loud_on_tenant_mismatch(): void
    {
        [$tenant, $user] = $this->customer();

        $this->resolveContext($tenant);

        try {
            $this->expectException(\RuntimeException::class);
            app(NotifiesCustomers::class)->notify(
                '01JQZZZZZZZZZZZZZZZZZZZZZZ',
                (string) $user->getAuthIdentifier(),
                'booking',
                'Cross-tenant attempt',
            );
        } finally {
            $this->clearContext();
        }
    }

    public function test_worker_resolves_the_tenant_context_itself(): void
    {
        [$tenant, $user] = $this->customer();
        $tenantId = (string) $tenant->getKey();
        $userId = (string) $user->getAuthIdentifier();

        // No ambient context — the worker posture (queue worker, drain).
        $this->clearContext();

        (new DispatchCustomerNotification(
            $tenantId,
            $userId,
            'booking',
            'Worker delivered',
            null,
            'evt_worker',
        ))->handle();

        $this->assertFalse(app(\App\Platform\Tenancy\TenantContext::class)->isResolved());

        $notification = CustomerNotification::query()
            ->withoutGlobalScopes()
            ->where('user_id', $userId)
            ->sole();

        $this->assertSame('Worker delivered', $notification->title);
        $this->assertSame($tenantId, (string) $notification->tenant_id);
    }

    public function test_routing_matrix_follows_preferences_flags_and_quiet_hours(): void
    {
        $all = ['notifications_email' => true, 'notifications_sms' => true];

        // Transactional: full matrix, quiet hours bypass.
        $this->assertSame(
            ['in_app', 'email', 'sms'],
            NotificationChannels::routed('booking', $all, true, false),
        );
        $this->assertSame(
            ['in_app', 'email', 'sms'],
            NotificationChannels::routed('booking', $all, true, true),
        );

        // Security always routes (packages/communication).
        $this->assertSame(
            ['in_app', 'email', 'sms'],
            NotificationChannels::routed('security', $all, true, true),
        );

        // Preference routing: opt-outs remove exactly their channel.
        $this->assertSame(
            ['in_app', 'sms'],
            NotificationChannels::routed('booking', ['notifications_email' => false, 'notifications_sms' => true], true, false),
        );
        $this->assertSame(
            ['in_app', 'email'],
            NotificationChannels::routed('booking', ['notifications_email' => true, 'notifications_sms' => false], true, false),
        );

        // The notify.email flag gates email even when the user allows it.
        $this->assertSame(
            ['in_app', 'sms'],
            NotificationChannels::routed('booking', $all, false, false),
        );

        // Quiet hours suppress service/marketing outbound legs only —
        // the in-app inbox always stays.
        $this->assertSame(
            ['in_app'],
            NotificationChannels::routed('support', $all, true, true),
        );
        $this->assertSame(
            ['in_app'],
            NotificationChannels::routed('marketing', $all, true, true),
        );
        $this->assertSame(
            ['in_app', 'email', 'sms'],
            NotificationChannels::routed('support', $all, true, false),
        );

        // Unknown kinds are transactional — never marketing.
        $this->assertSame(
            ['in_app', 'email', 'sms'],
            NotificationChannels::routed('mystery', $all, true, true),
        );
    }

    public function test_quiet_hours_window_math(): void
    {
        $night = ['timezone' => 'Asia/Kolkata', 'quiet_hours' => ['start' => '22:00', 'end' => '08:00']];
        $at = static fn (string $when): CarbonImmutable => CarbonImmutable::parse($when, 'Asia/Kolkata');

        $this->assertTrue(NotificationChannels::quietHoursActive($night, $at('2026-10-10 23:30:00')));
        $this->assertTrue(NotificationChannels::quietHoursActive($night, $at('2026-10-10 07:59:00')));
        $this->assertFalse(NotificationChannels::quietHoursActive($night, $at('2026-10-10 08:00:00')));
        $this->assertFalse(NotificationChannels::quietHoursActive($night, $at('2026-10-10 21:59:00')));
        $this->assertFalse(NotificationChannels::quietHoursActive($night, $at('2026-10-10 12:00:00')));

        // Non-crossing window.
        $day = ['timezone' => 'Asia/Kolkata', 'quiet_hours' => ['start' => '09:00', 'end' => '17:00']];
        $this->assertTrue(NotificationChannels::quietHoursActive($day, $at('2026-10-10 10:00:00')));
        $this->assertFalse(NotificationChannels::quietHoursActive($day, $at('2026-10-10 18:00:00')));

        // Timezone converts: 18:00 UTC is 23:30 IST (inside the night window).
        $utc = CarbonImmutable::parse('2026-10-10 18:00:00', 'UTC');
        $this->assertTrue(NotificationChannels::quietHoursActive($night, $utc));

        // Misconfiguration never suppresses.
        $this->assertFalse(NotificationChannels::quietHoursActive(['quiet_hours' => null]));
        $this->assertFalse(NotificationChannels::quietHoursActive(['quiet_hours' => ['start' => '25:00', 'end' => '08:00']]));
        $this->assertFalse(NotificationChannels::quietHoursActive(['quiet_hours' => ['start' => '22:00', 'end' => '22:00']]));
        $this->assertFalse(NotificationChannels::quietHoursActive(['timezone' => 'Mars/Olympus', 'quiet_hours' => ['start' => '22:00', 'end' => '08:00']]));
    }

    public function test_settings_surface_accepts_timezone_and_quiet_hours(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $updated = $actor()->patchJson('/api/v1/customers/settings', [
            'timezone' => 'Asia/Kolkata',
            'quiet_hours' => ['start' => '22:00', 'end' => '08:00'],
        ], $headers)->assertOk();

        $this->assertSame('Asia/Kolkata', $updated->json('data.settings.timezone'));
        // Field-wise compare: JSON key order is not contractual.
        $quiet = $updated->json('data.settings.quiet_hours');
        $this->assertSame('22:00', $quiet['start']);
        $this->assertSame('08:00', $quiet['end']);

        $actor()->patchJson('/api/v1/customers/settings', ['quiet_hours' => ['start' => '25:00', 'end' => '08:00']], $headers)
            ->assertStatus(422);
        $actor()->patchJson('/api/v1/customers/settings', ['quiet_hours' => ['start' => '22:00']], $headers)
            ->assertStatus(422);

        // Null clears the window (back to the closed default).
        $cleared = $actor()->patchJson('/api/v1/customers/settings', ['quiet_hours' => null], $headers)->assertOk();
        $this->assertNull($cleared->json('data.settings.quiet_hours'));

        $stored = CustomerSetting::query()
            ->withoutGlobalScopes()
            ->where('user_id', (string) $user->getAuthIdentifier())
            ->where('key', 'timezone')
            ->first();
        $this->assertNotNull($stored);
    }
}
