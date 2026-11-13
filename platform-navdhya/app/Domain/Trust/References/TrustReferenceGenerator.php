<?php

declare(strict_types=1);

namespace App\Domain\Trust\References;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Server-only, tenant-unique human-quotable reference generator for
 * Feedback, Support, Incident, and Evidence objects.
 *
 * Client-supplied references are NEVER accepted (spec §3, T-TS01-00 / TS-10).
 */
final class TrustReferenceGenerator
{
    public const KIND_CASE = 'CASE';

    public const KIND_GRIEVANCE = 'GRV';

    public const KIND_INCIDENT = 'INC';

    public const KIND_REVIEW = 'REV';

    public const KIND_EVIDENCE = 'EVD';

    /**
     * Mint a tenant-unique reference in the format NV-<KIND>-YYYYMM-<6-char>.
     */
    public static function mint(
        string $kind,
        string $tenantId,
        ?string $connection = null,
        ?string $uniqueTable = null,
        string $uniqueColumn = 'case_reference',
    ): string {
        $kind = strtoupper(trim($kind));
        if (! in_array($kind, [self::KIND_CASE, self::KIND_GRIEVANCE, self::KIND_INCIDENT, self::KIND_REVIEW, self::KIND_EVIDENCE], true)) {
            throw new \InvalidArgumentException("Unsupported trust reference kind: {$kind}");
        }

        $ym = now('UTC')->format('Ym');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            // Crockford-like uppercase suffix without easily confused characters.
            $suffix = strtoupper(substr(str_replace(['0', 'O', '1', 'I', 'L'], ['2', '3', '4', '5', '6'], (string) Str::ulid()), -6));
            $candidate = sprintf('NV-%s-%s-%s', $kind, $ym, $suffix);

            if ($uniqueTable === null) {
                return $candidate;
            }

            $exists = DB::connection($connection ?? 'tenant_shared')
                ->table($uniqueTable)
                ->where('tenant_id', $tenantId)
                ->where($uniqueColumn, $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        throw new \RuntimeException("Unable to mint unique trust reference for tenant {$tenantId}.");
    }
}

