<?php

namespace App\Modules\Jyotish\Http\Presenters;

use App\Domain\Jyotish\Profiles\JyotishProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

/**
 * Standard calculation result envelope for all ~250 tools (§2.3).
 */
final class JyotishResultEnvelope
{
    /**
     * Build standard successful calculation envelope.
     *
     * @param string $toolId Unique registry ID (e.g. "jyotish.chart.d1")
     * @param array<string, mixed> $data Computational output
     * @param JyotishProfile $profile Active calculation profile
     * @param string $rulesetCode Ruleset identifier
     * @param string $verificationStatus (unverified|golden_tested|practitioner_reviewed|disputed)
     * @param list<string> $warnings Non-fatal evaluation warnings
     * @param list<string> $unsupported Unsupported features or edge cases
     * @param int $generation Database generation sequence
     * @param string|null $calculationId Explicit ULID or generated identifier
     */
    public static function create(
        string $toolId,
        array $data,
        JyotishProfile $profile,
        string $rulesetCode = 'parashari-in',
        string $verificationStatus = 'golden_tested',
        array $warnings = [],
        array $unsupported = [],
        int $generation = 1,
        ?string $calculationId = null
    ): JsonResponse {
        $calcId = $calculationId ?? 'calc_' . strtolower((string) str_replace(['-', ':'], '', (string) CarbonImmutable::now()->getTimestampMs()));

        $payload = [
            'calculation_id' => $calcId,
            'tool_id' => $toolId,
            'status' => 'completed',
            'data' => $data,
            'methodology' => [
                'system' => 'parashari',
                'ayanamsa' => $profile->ayanamsa_code,
                'house_system' => $profile->house_system,
                'node_mode' => $profile->node_mode,
                'ruleset' => $rulesetCode,
                'rules_version' => $profile->rules_version,
            ],
            'engine' => [
                'profile_code' => $profile->profile_code,
                'ephemeris_model' => $profile->ephemeris_model,
                'ephemeris_version' => $profile->ephemeris_version,
                'generation' => $generation,
            ],
            'quality' => [
                'verification_status' => $verificationStatus,
                'warnings' => $warnings,
                'unsupported' => $unsupported,
            ],
            'created_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];

        return new JsonResponse($payload, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Wrap arbitrary payload into standard Jyotish result envelope array.
     *
     * @param mixed $data
     * @param string $schema
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    public static function wrap(mixed $data, string $schema = 'result_v1', array $meta = []): array
    {
        return [
            'data' => $data,
            'meta' => array_merge([
                'schema' => $schema,
                'request_id' => (string) (request()?->attributes?->get('request_id') ?? ''),
                'calculated_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            ], $meta),
        ];
    }
}

