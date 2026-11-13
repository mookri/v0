<?php

namespace App\Domain\Privacy\Consent;

use App\Domain\Privacy\Enums\ConsentPurpose;
use App\Domain\Privacy\Models\PrivacyConsent;
use App\Domain\Privacy\Support\IdentityFingerprint;
use App\Platform\Api\CodedHttpException;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\PlatformScope;
use DateTimeInterface;

/**
 * Granular, non-cascading consent ledger (SPEC §10 / E-TR-06 / Closes T-17).
 * Granting one purpose never unlocks another; withdrawing takes immediate effect.
 */
final class ConsentManager
{
    public function __construct(private readonly AuditWriter $audit)
    {
    }

    public function grant(
        string $tenantId,
        string $subjectIdentityId,
        ConsentPurpose $purpose,
        string $policyVersion = 'dpdp-2026-v1',
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?DateTimeInterface $expiresAt = null,
    ): PrivacyConsent {
        $consent = new PrivacyConsent();
        $consent->forceFill([
            'tenant_id' => $tenantId,
            'subject_identity_id' => $subjectIdentityId,
            'purpose' => $purpose->value,
            'granted' => true,
            'policy_version' => $policyVersion,
            'ip_hash' => $ipAddress !== null ? IdentityFingerprint::hash('ip', $ipAddress) : null,
            'user_agent' => $userAgent !== null ? substr($userAgent, 0, 255) : null,
            'granted_at' => now()->utc(),
            'withdrawn_at' => null,
            'expires_at' => $expiresAt,
        ])->save();

        $this->audit->record(
            'privacy.consent.granted',
            ['module' => 'privacy', 'resource_type' => 'privacy_consent', 'resource_id' => (string) $consent->getKey()],
            null,
            ['purpose' => $purpose->value, 'policy_version' => $policyVersion, 'subject_identity_id' => $subjectIdentityId],
        );

        return $consent;
    }

    public function withdraw(string $tenantId, string $subjectIdentityId, ConsentPurpose $purpose): int
    {
        return PlatformScope::run('privacy_consent_withdraw', function () use ($tenantId, $subjectIdentityId, $purpose): int {
            $now = now()->utc();
            $updated = PrivacyConsent::query()
                ->where('tenant_id', $tenantId)
                ->where('subject_identity_id', $subjectIdentityId)
                ->where('purpose', $purpose->value)
                ->where('granted', true)
                ->whereNull('withdrawn_at')
                ->update([
                    'granted' => false,
                    'withdrawn_at' => $now,
                ]);

            $this->audit->record(
                'privacy.consent.withdrawn',
                ['module' => 'privacy', 'resource_type' => 'privacy_consent', 'resource_id' => $subjectIdentityId],
                null,
                ['purpose' => $purpose->value, 'revoked_rows' => $updated],
            );

            return $updated;
        }, tenantId: $tenantId);
    }

    public function hasActiveConsent(string $tenantId, string $subjectIdentityId, ConsentPurpose $purpose): bool
    {
        return PlatformScope::run('privacy_consent_check', static function () use ($tenantId, $subjectIdentityId, $purpose): bool {
            $now = now()->utc();

            return PrivacyConsent::query()
                ->where('tenant_id', $tenantId)
                ->where('subject_identity_id', $subjectIdentityId)
                ->where('purpose', $purpose->value)
                ->where('granted', true)
                ->whereNull('withdrawn_at')
                ->where(static function ($q) use ($now): void {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                })
                ->exists();
        }, tenantId: $tenantId);
    }

    public function requireConsent(string $tenantId, string $subjectIdentityId, ConsentPurpose $purpose): void
    {
        if (! $this->hasActiveConsent($tenantId, $subjectIdentityId, $purpose)) {
            throw new CodedHttpException(
                403,
                'consent_required',
                sprintf('Active consent for purpose [%s] is required.', $purpose->value),
            );
        }
    }

    /**
     * @return list<string>
     */
    public function activePurposesFor(string $tenantId, string $subjectIdentityId): array
    {
        return PlatformScope::run('privacy_consent_list', static function () use ($tenantId, $subjectIdentityId): array {
            $now = now()->utc();

            return PrivacyConsent::query()
                ->where('tenant_id', $tenantId)
                ->where('subject_identity_id', $subjectIdentityId)
                ->where('granted', true)
                ->whereNull('withdrawn_at')
                ->where(static function ($q) use ($now): void {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                })
                ->pluck('purpose')
                ->unique()
                ->values()
                ->all();
        }, tenantId: $tenantId);
    }
}

