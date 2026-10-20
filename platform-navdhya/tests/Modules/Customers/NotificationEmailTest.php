<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Modules\Customers\Mail\NotificationEmail;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerNotification;
use App\Modules\Customers\Models\CustomerSetting;
use App\Modules\Customers\Models\NotificationDelivery;
use App\Platform\Features\FeatureFlags;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Email channel (N3): executes the email leg the N1 matrix routes.
 * The notify.email flag and the notifications_email preference gate
 * every send (routing decides, execution obeys), the per-channel claim
 * row makes retries resume instead of re-send, missing addresses
 * suppress visibly (never guessed), and the mailer stays
 * provider-agnostic (Mail::fake here, MAIL_* config in deployments).
 */
final class NotificationEmailTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(string $email = 'dev@example.com'): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create(['email' => $email]);
        $user->forceFill(['external_id' => 'cust_'.strtolower((string) Str::ulid())])->save();
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

    private function enableEmail(Tenant $tenant): void
    {
        $this->app->make(FeatureFlags::class)->setOverride('notify.email', true, (string) $tenant->getKey());
    }

    /**
     * @param  array<string, mixed>  $overrides  extra settings rows (key => value)
     */
    private function notify(Tenant $tenant, User $user, string $eventId, array $overrides = []): void
    {
        $this->resolveContext($tenant);
        try {
            foreach ($overrides as $key => $value) {
                (new CustomerSetting())->forceFill([
                    'user_id' => $user->id,
                    'key' => $key,
                    'value' => $value,
                ])->save();
            }

            app(NotifiesCustomers::class)->notify(
                (string) $tenant->getKey(),
                (string) $user->getAuthIdentifier(),
                'booking',
                'Booking created',
                'Your booking was created.',
                $eventId,
            );
        } finally {
            $this->clearContext();
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, NotificationDelivery>
     */
    private function deliveriesFor(User $user)
    {
        return NotificationDelivery::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->getAuthIdentifier())
            ->get();
    }

    public function test_flag_off_sends_no_mail_and_claims_no_row(): void
    {
        Mail::fake();
        [$tenant, $user] = $this->customer();

        $this->notify($tenant, $user, 'evt_flag_off');

        Mail::assertNothingOutgoing();
        $this->assertSame(0, $this->deliveriesFor($user)->count());
        // The in-app inbox is preference- and flag-independent (N1 posture).
        $this->assertSame(1, CustomerNotification::query()->withoutGlobalScopes()
            ->where('user_id', $user->getAuthIdentifier())->count());
    }

    public function test_email_sends_when_flag_and_preference_allow(): void
    {
        Mail::fake();
        [$tenant, $user] = $this->customer();
        $this->enableEmail($tenant);

        $this->notify($tenant, $user, 'evt_send_ok');

        Mail::assertSent(NotificationEmail::class, function (NotificationEmail $mail) use ($user): bool {
            return $mail->hasTo((string) $user->email)
                && $mail->kind === 'booking'
                && $mail->title === 'Booking created'
                && $mail->locale === 'en'
                && $mail->envelope()->subject === 'Booking update';
        });

        $delivery = $this->deliveriesFor($user)->sole();
        $this->assertSame(NotificationDelivery::STATUS_SENT, $delivery->status);
        $this->assertSame('email', $delivery->channel);
        $this->assertSame('booking', $delivery->kind);
        $this->assertSame('transactional', $delivery->category);
        $this->assertSame(
            'booking:evt_send_ok:'.(string) $user->getAuthIdentifier().':email',
            $delivery->dedup_key,
        );
        $this->assertNull($delivery->error);
    }

    public function test_replay_of_a_sent_leg_collapses_to_one_mail(): void
    {
        Mail::fake();
        [$tenant, $user] = $this->customer();
        $this->enableEmail($tenant);

        $this->notify($tenant, $user, 'evt_replay');
        $this->notify($tenant, $user, 'evt_replay');

        Mail::assertSentTimes(NotificationEmail::class, 1);
        $this->assertSame(1, $this->deliveriesFor($user)->count());
        $this->assertSame(NotificationDelivery::STATUS_SENT, $this->deliveriesFor($user)->sole()->status);
    }

    public function test_non_contactable_address_suppresses_the_leg_visibly(): void
    {
        Mail::fake();
        // The real OTP-signup posture: the NOT NULL column holds a
        // synthetic {phone}@phone.local placeholder — not a mailbox.
        [$tenant, $user] = $this->customer('919999999999@phone.local');
        $this->enableEmail($tenant);

        $this->notify($tenant, $user, 'evt_no_email');
        $this->notify($tenant, $user, 'evt_no_email');

        Mail::assertNothingOutgoing();
        $delivery = $this->deliveriesFor($user)->sole();
        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->status);
        $this->assertSame('no_email', $delivery->error);
        // In-app still delivered — only the email leg is suppressed.
        $this->assertSame(1, CustomerNotification::query()->withoutGlobalScopes()
            ->where('user_id', $user->getAuthIdentifier())->count());
    }

    public function test_preference_off_blocks_the_leg_before_any_claim(): void
    {
        Mail::fake();
        [$tenant, $user] = $this->customer();
        $this->enableEmail($tenant);

        $this->notify($tenant, $user, 'evt_pref_off', ['notifications_email' => false]);

        Mail::assertNothingOutgoing();
        $this->assertSame(0, $this->deliveriesFor($user)->count());
        $this->assertSame(1, CustomerNotification::query()->withoutGlobalScopes()
            ->where('user_id', $user->getAuthIdentifier())->count());
    }

    public function test_pending_claim_resumes_the_send_instead_of_skipping_it(): void
    {
        Mail::fake();
        [$tenant, $user] = $this->customer();
        $this->enableEmail($tenant);
        $key = 'booking:evt_resume:'.(string) $user->getAuthIdentifier().':email';

        // A prior attempt claimed and then died mid-send: pending row.
        $this->resolveContext($tenant);
        try {
            (new NotificationDelivery())->forceFill([
                'user_id' => $user->id,
                'kind' => 'booking',
                'category' => 'transactional',
                'channel' => 'email',
                'dedup_key' => $key,
                'status' => NotificationDelivery::STATUS_PENDING,
            ])->save();
        } finally {
            $this->clearContext();
        }

        $this->notify($tenant, $user, 'evt_resume');

        Mail::assertSentTimes(NotificationEmail::class, 1);
        $this->assertSame(NotificationDelivery::STATUS_SENT, $this->deliveriesFor($user)->sole()->status);
    }

    public function test_unkeyed_one_shot_sends_without_a_claim_row(): void
    {
        Mail::fake();
        [$tenant, $user] = $this->customer();
        $this->enableEmail($tenant);

        $this->resolveContext($tenant);
        try {
            app(NotifiesCustomers::class)->notify(
                (string) $tenant->getKey(),
                (string) $user->getAuthIdentifier(),
                'booking',
                'One-shot fact',
            );
        } finally {
            $this->clearContext();
        }

        Mail::assertSentTimes(NotificationEmail::class, 1);
        // Documented posture: no event identity, no claim — nothing to key on.
        $this->assertSame(0, $this->deliveriesFor($user)->count());
    }
}
