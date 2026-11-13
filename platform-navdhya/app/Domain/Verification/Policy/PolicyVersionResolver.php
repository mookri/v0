<?php

namespace App\Domain\Verification\Policy;

use App\Domain\Verification\Models\VerificationPolicyVersion;

/**
 * Resolves track-level verification policy rules and version metadata.
 */
final class PolicyVersionResolver
{
    /**
     * @return array{
     *   version: string,
     *   track: string,
     *   require_dual_control: bool,
     *   required_claims: list<string>,
     *   default_expiry_days: int
     * }
     */
    public function resolveForTrack(string $track): array
    {
        $row = VerificationPolicyVersion::query()
            ->where('track', $track)
            ->whereNull('deprecated_at')
            ->where('active_from', '<=', now()->utc())
            ->orderByDesc('active_from')
            ->first();

        if ($row !== null && is_array($row->rules_json)) {
            return [
                'version' => $row->version,
                'track' => $track,
                'require_dual_control' => (bool) ($row->rules_json['require_dual_control'] ?? true),
                'required_claims' => array_values(array_map('strval', (array) ($row->rules_json['required_claims'] ?? ['identity']))),
                'default_expiry_days' => (int) ($row->rules_json['default_expiry_days'] ?? 365),
            ];
        }

        return match ($track) {
            'pandit' => [
                'version' => 'pandit-policy-v2',
                'track' => 'pandit',
                'require_dual_control' => true,
                'required_claims' => ['identity', 'qualification', 'antecedent'],
                'default_expiry_days' => 365,
            ],
            'temple' => [
                'version' => 'temple-policy-v2',
                'track' => 'temple',
                'require_dual_control' => true,
                'required_claims' => ['identity', 'institutional_authority', 'financial'],
                'default_expiry_days' => 365,
            ],
            'partner-vendor' => [
                'version' => 'vendor-policy-v2',
                'track' => 'partner-vendor',
                'require_dual_control' => true,
                'required_claims' => ['identity', 'tax', 'financial'],
                'default_expiry_days' => 365,
            ],
            default => [
                'version' => 'default-policy-v2',
                'track' => $track,
                'require_dual_control' => false,
                'required_claims' => ['identity'],
                'default_expiry_days' => 365,
            ],
        };
    }
}
