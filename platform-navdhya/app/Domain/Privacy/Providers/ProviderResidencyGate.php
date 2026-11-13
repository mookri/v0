<?php

namespace App\Domain\Privacy\Providers;

use App\Domain\Privacy\Models\VerificationProviderRegistration;
use App\Platform\Api\CodedHttpException;

/**
 * Fails closed whenever an external verification subprocessor lacks DPA,
 * subprocessor approval, or enabled residency registration (T-TR06-05).
 */
final class ProviderResidencyGate
{
    public function assertApproved(string $providerCode): VerificationProviderRegistration
    {
        $row = VerificationProviderRegistration::query()
            ->where('provider_code', $providerCode)
            ->first();

        if ($row === null || ! $row->enabled || ! $row->dpa_executed || ! $row->subprocessor_approved) {
            throw new CodedHttpException(
                403,
                'provider_not_compliant',
                sprintf('Verification provider [%s] lacks executed DPA or approved subprocessor residency.', $providerCode),
            );
        }

        return $row;
    }
}

