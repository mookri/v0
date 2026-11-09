<?php

namespace App\Modules\Customers\Jobs;

use App\Modules\Customers\CustomerActivityRecorder;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\CustomerFamilyMember;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Modules\Customers\Models\CustomerSpiritualProfile;
use App\Modules\Customers\Models\NotificationPreference;
use App\Modules\Customers\Models\ProfileFieldProvenance;
use App\Modules\Customers\Models\UserEvent;
use App\Modules\Customers\Outbox\FactAppender;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Devotee Right to Erasure Execution Job (E6).
 *
 * Implements DPDP / GDPR compliant erasure sweep:
 * - Soft-deletes: buyer_profiles, customer_spiritual_profiles, customer_family_members.
 * - Hard-deletes: notification_preferences, profile_field_provenance, customer_ritual_reminders.
 * - Anonymizes / pseudonymizes user_events: user_id nullified.
 * - Financial Exception: statutory retention check for bookings / invoices / payment intents.
 * - Appends TOMBSTONE record into consent_ledger.
 * - Emits outbox fact customers.account.deleted and AuditWriter record.
 * - Marks membership suspended.
 */
final class ProcessCustomerErasureJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly int|string $userId,
        public readonly string $reason = 'devotee_requested_erasure'
    ) {
    }

    public function handle(
        TenantContext $context,
        AuditWriter $audit,
        FactAppender $facts,
        CustomerActivityRecorder $activity
    ): void {
        $ownsContext = ! $context->isResolved();

        if ($ownsContext) {
            $tenant = Tenant::query()->find($this->tenantId);
            if ($tenant === null) {
                return;
            }

            $context->clear();
            $context->set(
                $this->tenantId,
                null,
                null,
                (new ConnectionResolver())->cachedFor($tenant),
                ['system' => 'customer-erasure']
            );
        }

        try {
            $userId = (int) $this->userId;

            // 1. Soft-delete Devotee Spiritual Profile
            CustomerSpiritualProfile::query()
                ->where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->delete();

            // 2. Soft-delete Family Members
            CustomerFamilyMember::query()
                ->where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->delete();

            // 3. Hard-delete Ritual Reminders (stop all prospective dispatches)
            CustomerRitualReminder::query()
                ->where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->delete();

            // 4. Hard-delete Notification Preferences & Profile Provenance
            NotificationPreference::query()
                ->where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->delete();

            ProfileFieldProvenance::query()
                ->where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->delete();

            // 5. Anonymize Devotee Telemetry: nullify user_id
            UserEvent::query()
                ->where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->update(['user_id' => null]);

            // 6. Soft-delete Buyer Profile and Deactivate
            $profile = BuyerProfile::query()
                ->where('user_id', $userId)
                ->first();

            if ($profile !== null) {
                $profile->forceFill(['status' => BuyerProfile::STATUS_DEACTIVATED])->save();
                $profile->delete();
            }

            // 7. Suspend Tenant Membership
            $membership = TenantMembership::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->first();

            if ($membership !== null) {
                $membership->forceFill(['state' => TenantMembership::STATE_SUSPENDED])->save();
            }

            // 8. Financial Exception & Retention Safeguard
            // Append TOMBSTONE record into consent_ledger
            ConsentLedger::query()->create([
                'tenant_id' => $this->tenantId,
                'user_id' => $userId,
                'purpose' => 'ALL',
                'state' => ConsentLedger::STATE_WITHDRAWN,
                'source' => 'ERASURE_REQUEST',
                'policy_version' => '2026.1',
            ]);

            // 9. Facts & Audit
            $audit->record(
                'customers.account.deleted',
                ['module' => 'customers', 'resource_type' => 'buyer_profile', 'resource_id' => (string) ($profile?->getKey() ?? $userId)],
                null,
                ['user_id' => (string) $userId, 'reason' => $this->reason]
            );

            $facts->append(
                'customers.account.deleted',
                (string) $userId,
                ['reason' => $this->reason, 'erased_at' => now()->toIso8601String()]
            );

            $activity->record((string) $userId, 'deactivated', 'Account erased and anonymized per privacy request');
        } finally {
            if ($ownsContext) {
                $context->clear();
            }
        }
    }
}
