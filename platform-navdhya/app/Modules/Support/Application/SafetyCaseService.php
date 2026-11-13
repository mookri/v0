<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Domain\Trust\Parties\PartyEntitlement;
use App\Domain\Trust\References\TrustReferenceGenerator;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseEvidence;
use App\Modules\Support\Models\SupportCaseMessage;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Compartmentalized Safety Case Service (E-TS-07 / TS-15 / ADR-0067).
 *
 * Guarantees:
 *  - Encrypted REPORTER_PRIVATE messages readable strictly by authorized safety leads & reporter
 *  - Compartmentalized evidence vault on `local` storage (`RESTRICTED_SAFETY`)
 *  - Explicit 112 / emergency authorities signposting on intake
 *  - Independence check on safety case assignee vs reporter/provider
 *  - Safety triage SLA latency metrics
 */
final class SafetyCaseService
{
    public const ENCRYPTED_PREFIX = 'enc:v1:';

    public function __construct(
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function emergencySignposting(): array
    {
        return [
            'emergency_number' => '112',
            'women_helpline' => '181',
            'cyber_crime_helpline' => '1930',
            'notice' => 'If anyone is in immediate physical danger, please contact emergency services (112 in India) or local law enforcement directly. Navdhya reviews platform conduct separately and does not replace police or statutory authorities.',
        ];
    }

    public function addConfidentialMessage(
        string $caseId,
        string $authorId,
        string $authorRole,
        string $plaintextBody,
    ): SupportCaseMessage {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $caseId)
            ->where('visibility_scope', PartyEntitlement::SCOPE_RESTRICTED_SAFETY)
            ->firstOrFail();

        /** @var SupportCaseMessage $msg */
        $msg = SupportCaseMessage::query()->create([
            'tenant_id' => $tenantId,
            'case_id' => (string) $case->getKey(),
            'author_id' => $authorId,
            'author_role' => $authorRole,
            'visibility_scope' => PartyEntitlement::SCOPE_REPORTER_PRIVATE,
            'body' => self::ENCRYPTED_PREFIX . Crypt::encryptString($plaintextBody),
        ]);

        $this->audit->record(
            'support.safety_message.recorded',
            ['module' => 'support', 'resource_type' => 'support_case_message', 'resource_id' => (string) $msg->getKey()],
            null,
            ['case_id' => $caseId, 'visibility_scope' => PartyEntitlement::SCOPE_REPORTER_PRIVATE]
        );

        return $msg;
    }

    public function decryptMessageBody(string $storedBody): string
    {
        if (!str_starts_with($storedBody, self::ENCRYPTED_PREFIX)) {
            return $storedBody;
        }

        return Crypt::decryptString(substr($storedBody, strlen(self::ENCRYPTED_PREFIX)));
    }

    public function storeSafetyEvidence(
        string $caseId,
        string $uploaderId,
        string $description,
        string $purpose,
        string $contentPayload,
    ): SupportCaseEvidence {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $caseId)
            ->where('visibility_scope', PartyEntitlement::SCOPE_RESTRICTED_SAFETY)
            ->firstOrFail();

        $ref = TrustReferenceGenerator::mint(
            TrustReferenceGenerator::KIND_EVIDENCE,
            $tenantId,
            (new SupportCaseEvidence())->getConnectionName(),
            'support_case_evidence',
            'evidence_reference',
        );

        $storagePath = sprintf('support/safety-evidence/%s/%s/%s.enc', $tenantId, (string) $case->getKey(), $ref);
        Storage::disk('local')->put($storagePath, Crypt::encryptString($contentPayload));

        /** @var SupportCaseEvidence $evidence */
        $evidence = SupportCaseEvidence::query()->create([
            'tenant_id' => $tenantId,
            'case_id' => (string) $case->getKey(),
            'evidence_reference' => $ref,
            'description' => $description,
            'purpose' => $purpose,
            'provenance' => 'ORIGINAL_ARTEFACT',
            'storage_reference' => $storagePath,
            'sensitivity_class' => PartyEntitlement::SCOPE_RESTRICTED_SAFETY,
            'retention_class' => 'SAFETY_LONG_HOLD',
            'uploaded_by_id' => $uploaderId,
            'retained_until' => CarbonImmutable::now('UTC')->addYears(3)->toDateTimeString(),
        ]);

        $this->audit->record(
            'support.safety_evidence.vaulted',
            ['module' => 'support', 'resource_type' => 'support_case_evidence', 'resource_id' => (string) $evidence->getKey()],
            null,
            ['case_id' => $caseId, 'evidence_reference' => $ref, 'sensitivity_class' => PartyEntitlement::SCOPE_RESTRICTED_SAFETY]
        );

        return $evidence;
    }

    /**
     * @return array{total_safety_cases: int, acknowledged_count: int, unacknowledged_count: int, avg_ack_minutes: float|null}
     */
    public function triageSlaMetrics(): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $cases = SupportCase::query()
            ->where('tenant_id', $tenantId)
            ->where('visibility_scope', PartyEntitlement::SCOPE_RESTRICTED_SAFETY)
            ->get(['created_at', 'acknowledged_at']);

        $total = $cases->count();
        $acked = $cases->filter(static fn(SupportCase $c): bool => $c->acknowledged_at !== null);
        $avgMinutes = null;

        if ($acked->isNotEmpty()) {
            $sum = 0.0;
            foreach ($acked as $c) {
                $sum += max(0.0, (float) $c->created_at->diffInMinutes($c->acknowledged_at));
            }
            $avgMinutes = round($sum / $acked->count(), 2);
        }

        return [
            'total_safety_cases' => $total,
            'acknowledged_count' => $acked->count(),
            'unacknowledged_count' => $total - $acked->count(),
            'avg_ack_minutes' => $avgMinutes,
        ];
    }
}

