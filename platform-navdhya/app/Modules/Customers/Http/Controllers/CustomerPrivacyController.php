<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Jobs\ProcessCustomerErasureJob;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\CustomerAddress;
use App\Modules\Customers\Models\CustomerFamilyMember;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Modules\Customers\Models\CustomerSpiritualProfile;
use App\Modules\Customers\Models\NotificationPreference;
use App\Modules\Customers\Models\UserEvent;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Devotee Privacy, Data Export & Right to Erasure Controller (E6).
 *
 * Implements DPDP / GDPR Article 15 (Right of Access / Export) and
 * Article 17 (Right to Erasure) under strict privacy governance.
 */
final class CustomerPrivacyController extends CustomerSelfServiceController
{
    public function export(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (string) $context->tenantId();

        $buyerProfile = BuyerProfile::query()
            ->where('user_id', $userId)
            ->first();

        $spiritualProfile = CustomerSpiritualProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        $familyMembers = CustomerFamilyMember::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->get();

        $ritualReminders = CustomerRitualReminder::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->get();

        $consentLedger = ConsentLedger::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get();

        $preferences = NotificationPreference::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->get();

        $addresses = CustomerAddress::query()
            ->where('user_id', $userId)
            ->get();

        $events = UserEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('occurred_at')
            ->limit(500)
            ->get()
            ->map(fn (UserEvent $e) => [
                'event_type' => $e->event_type,
                'properties' => $e->properties,
                'source' => $e->source,
                'occurred_at' => $e->occurred_at?->toIso8601String(),
            ]);

        $exportData = [
            'devotee' => [
                'user_id' => $userId,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'locale' => $buyerProfile !== null ? $buyerProfile->locale : 'en',
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'spiritual_profile' => $spiritualProfile ? [
                'gotra' => $spiritualProfile->gotra,
                'nakshatra' => $spiritualProfile->nakshatra,
                'rashi' => $spiritualProfile->rashi,
                'kuladevata_name' => $spiritualProfile->kuladevata_name,
                'kuladevata_temple_id' => $spiritualProfile->kuladevata_temple_id,
                'birth_date' => $spiritualProfile->birth_date,
                'birth_time' => $spiritualProfile->birth_time,
                'birth_place' => $spiritualProfile->birth_place,
                'birth_timezone' => $spiritualProfile->birth_timezone,
                'tradition' => $spiritualProfile->tradition,
            ] : null,
            'family_members' => $familyMembers->map(fn (CustomerFamilyMember $m) => [
                'id' => $m->id,
                'full_name' => $m->full_name,
                'relation' => $m->relation,
                'nakshatra' => $m->nakshatra,
                'rashi' => $m->rashi,
                'birth_date' => $m->birth_date,
                'death_date' => $m->death_date,
                'data_subject_kind' => $m->data_subject_kind,
            ]),
            'ritual_reminders' => $ritualReminders->map(fn (CustomerRitualReminder $r) => [
                'id' => $r->id,
                'type' => $r->type,
                'title' => $r->title,
                'anchor_rule' => $r->anchor_rule,
                'advance_days' => $r->advance_days,
                'next_occurrence_at' => $r->next_occurrence_at?->toIso8601String(),
                'is_active' => $r->is_active,
            ]),
            'consent_ledger' => $consentLedger->map(fn (ConsentLedger $c) => [
                'purpose' => $c->purpose,
                'state' => $c->state,
                'source' => $c->source,
                'policy_version' => $c->policy_version,
                'timestamp' => $c->created_at?->toIso8601String(),
            ]),
            'notification_preferences' => $preferences->map(fn (NotificationPreference $p) => [
                'category' => $p->category,
                'channel' => $p->channel,
                'is_enabled' => $p->is_enabled,
            ]),
            'addresses' => $addresses->map(fn (CustomerAddress $a): array => [
                'label' => $a->label,
                'line1' => $a->line1,
                'line2' => $a->line2,
                'city' => $a->city,
                'state' => $a->state,
                'postal_code' => $a->postal_code,
                'is_default' => $a->is_default,
            ]),
            'user_events' => $events,
        ];

        return new JsonResponse([
            'status' => 'success',
            'data' => $exportData,
            'meta' => [
                'tenant_id' => $tenantId,
                'exported_at' => now()->toIso8601String(),
                'format' => 'json',
                'request_id' => $this->requestId($request),
            ],
        ]);
    }

    public function erasure(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (string) $context->tenantId();
        $reason = (string) $request->input('reason', 'devotee_requested_erasure');

        // Execute erasure job (dispatch or sync)
        ProcessCustomerErasureJob::dispatchSync($tenantId, $userId, $reason);

        return new JsonResponse([
            'status' => 'accepted',
            'message' => 'Devotee profile erased, telemetry anonymized, and account deactivated.',
            'financial_exception' => [
                'retained_records' => ['bookings', 'finance_documents', 'invoices'],
                'legal_basis' => 'statutory_accounting_and_tax_compliance',
            ],
            'meta' => [
                'tenant_id' => $tenantId,
                'request_id' => $this->requestId($request),
            ],
        ], 200);
    }
}
