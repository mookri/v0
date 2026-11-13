<?php

namespace App\Domain\Verification\Issuers;

use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Models\VerificationCredential;
use Illuminate\Database\Eloquent\Model;

/**
 * Revokes a registered credential and cascades revocation ONLY to dependent qualification claims,
 * preserving unrelated identity claims intact (T-TR03-07.1).
 */
final class CredentialRevocationService
{
    public function __construct(
        private readonly ClaimManager $claims,
    ) {
    }

    public function revoke(
        string $tenantId,
        string $credentialId,
        string $actorId,
        string $reason,
        ?Model $subjectModel = null,
    ): VerificationCredential {
        $credential = VerificationCredential::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($credentialId);

        $credential->forceFill([
            'revoked_at' => now()->utc(),
            'revocation_reason' => $reason,
        ])->save();

        if ($credential->claim_id !== null) {
            $claim = VerificationClaim::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('claim_kind', ClaimKind::QUALIFICATION->value)
                ->where('status', ClaimStatus::GRANTED->value)
                ->find($credential->claim_id);

            if ($claim !== null) {
                $this->claims->revokeClaim(
                    $tenantId,
                    (string) $claim->getKey(),
                    $actorId,
                    $reason,
                    $subjectModel,
                );
            }
        }

        return $credential->fresh();
    }
}

