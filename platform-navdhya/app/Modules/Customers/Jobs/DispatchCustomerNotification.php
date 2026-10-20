<?php

namespace App\Modules\Customers\Jobs;

use App\Models\User;
use App\Modules\Customers\Mail\NotificationEmail;
use App\Modules\Customers\Models\CustomerNotification;
use App\Modules\Customers\Models\CustomerSetting;
use App\Modules\Customers\Models\NotificationDelivery;
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
use Illuminate\Support\Facades\Mail;

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
 * packages/communication) is computed here; in_app delivers through the
 * unique (tenant, dedup_key) index (concurrent duplicates collapse) and
 * email delivers through the per-channel claim row (N3 — resume instead
 * of re-send across retries), both provider-agnostic (mailer config).
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

            // Email is routed above by the N1 matrix (flag + preference +
            // quiet hours) and executed here (N3): provider-agnostic
            // Laravel mailer, claim-row idempotency below. Sms stays
            // routed-but-undelivered until its provider track.
            if (in_array(NotificationChannels::CHANNEL_EMAIL, $channels, true)) {
                $this->deliverEmail($settings);
            }
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
     * Email delivery (N3): claim the (tenant, dedup_key) delivery row
     * before sending — pending → sent flips after the provider hand-off,
     * so a retry after a partial run resumes its own row instead of
     * re-sending, and a replay of a sent row collapses. No address on
     * record writes a suppressed row (the leg can never go out — visible
     * to ops, never retried). A null eventId is a one-shot fact: it
     * sends without a claim row (documented: unkeyed legs cannot dedup).
     *
     * A send exception propagates: the job's try/backoff retries it and
     * the pending row is the resume point; the attempts cap leaves the
     * row pending for ops (failed_jobs carries the cause).
     *
     * @param  array<string, mixed>  $settings  resolved CustomerSetting values (key => value)
     */
    private function deliverEmail(array $settings): void
    {
        $email = $this->emailAddress();
        $dedupKey = $this->eventId !== null
            ? NotificationChannels::dedupKey($this->kind, $this->eventId, $this->userId, NotificationChannels::CHANNEL_EMAIL)
            : null;

        if ($dedupKey === null) {
            if ($email !== null) {
                $this->sendEmail($email, $settings);
            }

            return;
        }

        $delivery = NotificationDelivery::query()->where('dedup_key', $dedupKey)->first();

        if ($delivery === null) {
            $claim = new NotificationDelivery();
            $claim->forceFill([
                'user_id' => $this->userId,
                'kind' => $this->kind,
                'category' => NotificationChannels::categoryFor($this->kind),
                'channel' => NotificationChannels::CHANNEL_EMAIL,
                'dedup_key' => $dedupKey,
                'status' => $email === null
                    ? NotificationDelivery::STATUS_SUPPRESSED
                    : NotificationDelivery::STATUS_PENDING,
                'error' => $email === null ? 'no_email' : null,
            ]);

            try {
                $claim->save();
                $delivery = $claim;
            } catch (UniqueConstraintViolationException) {
                $delivery = NotificationDelivery::query()->where('dedup_key', $dedupKey)->first();
            }
        }

        if ($delivery === null || $delivery->status !== NotificationDelivery::STATUS_PENDING) {
            return; // sent or suppressed already — replay collapsed
        }

        if ($email === null) {
            // The address disappeared since the failed attempt — record it.
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SUPPRESSED,
                'error' => 'no_email',
            ])->save();

            return;
        }

        $this->sendEmail($email, $settings);

        $delivery->forceFill(['status' => NotificationDelivery::STATUS_SENT, 'error' => null])->save();
    }

    /**
     * The recipient address (users.email). Absent or blank = no contact
     * point; the synthetic `{phone}@phone.local` placeholder OTP signups
     * write (the column is NOT NULL) is also not a mailbox — the same
     * posture CustomerSelfServiceController::maskEmail() reports. Inlined
     * here because the job (module core) must not import the HTTP layer.
     * Never guessed: a non-contactable address suppresses the leg.
     */
    private function emailAddress(): ?string
    {
        $email = User::query()->whereKey($this->userId)->value('email');

        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);
        $at = strrpos($email, '@');

        if ($email === '' || ($at !== false && substr($email, $at + 1) === 'phone.local')) {
            return null;
        }

        return $email;
    }

    /**
     * @param  array<string, mixed>  $settings  resolved CustomerSetting values (key => value)
     */
    private function sendEmail(string $email, array $settings): void
    {
        $locale = $settings['locale'] ?? null;

        Mail::to($email)->send(new NotificationEmail(
            $this->kind,
            $this->title,
            $this->body,
            is_string($locale) && $locale !== '' ? $locale : 'en',
        ));
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
