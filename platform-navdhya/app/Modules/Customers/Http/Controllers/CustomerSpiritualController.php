<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Domain\Panchang\Contracts\ComputesPanchang;
use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\CustomerFamilyMember;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Modules\Customers\Models\CustomerSpiritualProfile;
use App\Modules\Customers\Models\NotificationPreference;
use App\Modules\Customers\Models\ProfileFieldProvenance;
use App\Modules\Customers\Outbox\FactAppender;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Devotee Spiritual Profile, Family Lineage, Ritual Reminders & Consent (E1 / E2 / E3).
 */
final class CustomerSpiritualController extends CustomerSelfServiceController
{
    private readonly FactAppender $facts;

    public function __construct(
        private readonly AuditWriter $audit,
        private readonly ComputesPanchang $panchang,
        ?FactAppender $facts = null
    ) {
        $this->facts = $facts ?? app(FactAppender::class);
    }

    // --- Spiritual Profile ---

    public function showSpiritual(Request $request, TenantContext $context): JsonResponse
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

        $profile = CustomerSpiritualProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        return new JsonResponse([
            'status' => 'success',
            'data' => $profile ? [
                'id' => $profile->id,
                'gotra' => $profile->gotra,
                'nakshatra' => $profile->nakshatra,
                'rashi' => $profile->rashi,
                'kuladevata_name' => $profile->kuladevata_name,
                'kuladevata_temple_id' => $profile->kuladevata_temple_id,
                'birth_date' => $profile->birth_date,
                'birth_time' => $profile->birth_time,
                'birth_place' => $profile->birth_place,
                'birth_timezone' => $profile->birth_timezone,
                'tradition' => $profile->tradition,
            ] : null,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function updateSpiritual(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'gotra' => ['nullable', 'string', 'max:100'],
            'nakshatra' => ['nullable', 'string', 'max:50'],
            'rashi' => ['nullable', 'string', 'max:50'],
            'kuladevata_name' => ['nullable', 'string', 'max:190'],
            'kuladevata_temple_id' => ['nullable', 'string', 'max:26'],
            'birth_date' => ['nullable', 'date'],
            'birth_time' => ['nullable', 'string', 'max:10'],
            'birth_place' => ['nullable', 'string', 'max:190'],
            'birth_timezone' => ['nullable', 'string', 'max:50'],
            'tradition' => ['nullable', 'string', 'in:AMANTA,PURNIMANTA'],
        ]);

        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (string) $context->tenantId();

        $profile = CustomerSpiritualProfile::query()
            ->updateOrCreate(
                ['tenant_id' => $tenantId, 'user_id' => $userId],
                array_merge($validated, ['tradition' => $validated['tradition'] ?? CustomerSpiritualProfile::TRADITION_AMANTA])
            );

        // Update provenance
        foreach ($validated as $field => $val) {
            if ($val !== null) {
                ProfileFieldProvenance::query()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'user_id' => $userId, 'field_name' => $field],
                    ['source' => ProfileFieldProvenance::SOURCE_USER_ENTERED, 'confidence' => 100]
                );
            }
        }

        // Facts & Audit
        $this->facts->append('customers.spiritual_profile.updated', (string) $userId, [
            'gotra' => $profile->gotra,
            'nakshatra' => $profile->nakshatra,
            'rashi' => $profile->rashi,
            'tradition' => $profile->tradition,
        ]);

        $this->audit->record(
            'customers.spiritual_profile.updated',
            ['module' => 'customers', 'resource_type' => 'customer_spiritual_profile', 'resource_id' => $profile->id],
            null,
            ['user_id' => (string) $userId]
        );

        return new JsonResponse([
            'status' => 'success',
            'data' => $profile,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // --- Family Lineage ---

    public function listFamily(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $members = CustomerFamilyMember::query()
            ->where('tenant_id', (string) $context->tenantId())
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->orderBy('created_at')
            ->get();

        return new JsonResponse([
            'status' => 'success',
            'data' => $members,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function storeFamily(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:190'],
            'relation' => ['required', 'string', 'max:50'],
            'nakshatra' => ['nullable', 'string', 'max:50'],
            'rashi' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'death_date' => ['nullable', 'date'],
            'data_subject_kind' => ['required', 'string', 'in:SELF,DECEASED,THIRD_PARTY'],
            'consent_ref' => ['nullable', 'string', 'max:100'],
        ]);

        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (string) $context->tenantId();

        $member = CustomerFamilyMember::query()->create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
        ]));

        $this->facts->append('customers.family_member.updated', (string) $userId, [
            'family_member_id' => $member->id,
            'relation' => $member->relation,
            'kind' => $member->data_subject_kind,
        ]);

        return new JsonResponse([
            'status' => 'created',
            'data' => $member,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function destroyFamily(string $id, Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $member = CustomerFamilyMember::query()
            ->where('tenant_id', (string) $context->tenantId())
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->where('id', $id)
            ->firstOrFail();

        $member->delete();

        return new JsonResponse([
            'status' => 'deleted',
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // --- Ritual Reminders ---

    public function listReminders(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $reminders = CustomerRitualReminder::query()
            ->where('tenant_id', (string) $context->tenantId())
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->orderBy('next_occurrence_at')
            ->get();

        return new JsonResponse([
            'status' => 'success',
            'data' => $reminders,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function storeReminder(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'family_member_id' => ['nullable', 'string', 'max:26'],
            'type' => ['required', 'string', 'in:SHRADDHA,LUNAR_BIRTHDAY,SOLAR_BIRTHDAY,WEDDING_ANNIVERSARY,CUSTOM_TITHI'],
            'title' => ['required', 'string', 'max:190'],
            'anchor_rule' => ['required', 'array'],
            'advance_days' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (string) $context->tenantId();

        // Calculate next occurrence using Panchang engine
        $nextOccurrenceAt = null;
        try {
            $occurrence = $this->panchang->occurrenceForAnchor(
                $validated['anchor_rule'],
                CarbonImmutable::now('UTC'),
                $validated['anchor_rule']['timezone'] ?? 'Asia/Kolkata',
                $validated['anchor_rule']['tradition'] ?? 'AMANTA'
            );
            $nextOccurrenceAt = CarbonImmutable::parse($occurrence->sunriseUtc);
        } catch (\Throwable) {
            // Leave next_occurrence_at null if calculation fails or out-of-corpus
        }

        $reminder = CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'family_member_id' => $validated['family_member_id'] ?? null,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'anchor_rule' => $validated['anchor_rule'],
            'advance_days' => $validated['advance_days'] ?? [7, 3, 1],
            'next_occurrence_at' => $nextOccurrenceAt,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return new JsonResponse([
            'status' => 'created',
            'data' => $reminder,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function destroyReminder(string $id, Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $reminder = CustomerRitualReminder::query()
            ->where('tenant_id', (string) $context->tenantId())
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->where('id', $id)
            ->firstOrFail();

        $reminder->delete();

        return new JsonResponse([
            'status' => 'deleted',
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // --- Consent Ledger ---

    public function getConsent(Request $request, TenantContext $context): JsonResponse
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

        $purposes = [
            ConsentLedger::PURPOSE_TRANSACTIONAL,
            ConsentLedger::PURPOSE_RITUAL_REMINDERS,
            ConsentLedger::PURPOSE_ANALYTICS,
            ConsentLedger::PURPOSE_MARKETING,
        ];

        $status = [];
        foreach ($purposes as $purpose) {
            $latest = ConsentLedger::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('purpose', $purpose)
                ->orderByDesc('id')
                ->first();

            $status[$purpose] = [
                'state' => $latest !== null ? $latest->state : ($purpose === ConsentLedger::PURPOSE_TRANSACTIONAL ? ConsentLedger::STATE_GRANTED : ConsentLedger::STATE_WITHDRAWN),
                'updated_at' => $latest?->created_at?->toIso8601String(),
                'source' => $latest !== null ? $latest->source : 'DEFAULT',
            ];
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => $status,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function updateConsent(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'purpose' => ['required', 'string', 'in:TRANSACTIONAL,RITUAL_REMINDERS,ANALYTICS,MARKETING'],
            'state' => ['required', 'string', 'in:GRANTED,WITHDRAWN'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (string) $context->tenantId();

        $entry = ConsentLedger::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'purpose' => $validated['purpose'],
            'state' => $validated['state'],
            'source' => $validated['source'] ?? 'SETTINGS_PORTAL',
            'policy_version' => '2026.1',
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null,
        ]);

        $this->facts->append('customers.consent.changed', (string) $userId, [
            'purpose' => $entry->purpose,
            'state' => $entry->state,
        ]);

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'purpose' => $entry->purpose,
                'state' => $entry->state,
                'timestamp' => $entry->created_at->toIso8601String(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // --- Notification Preferences Matrix ---

    public function getPreferences(Request $request, TenantContext $context): JsonResponse
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

        $prefs = NotificationPreference::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->get();

        return new JsonResponse([
            'status' => 'success',
            'data' => $prefs,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function updatePreferences(Request $request, TenantContext $context): JsonResponse
    {
        $denial = $this->machineDenial($request);
        if ($denial !== null) {
            return $denial;
        }

        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:TRANSACTIONAL,BOOKING,REMINDER,MARKETING'],
            'channel' => ['required', 'string', 'in:IN_APP,SMS,WHATSAPP,EMAIL'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (string) $context->tenantId();

        $pref = NotificationPreference::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'category' => $validated['category'],
                'channel' => $validated['channel'],
            ],
            ['is_enabled' => $validated['is_enabled']]
        );

        return new JsonResponse([
            'status' => 'success',
            'data' => $pref,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
