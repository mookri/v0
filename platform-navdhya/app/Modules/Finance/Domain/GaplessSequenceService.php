<?php

namespace App\Modules\Finance\Domain;

use App\Modules\Finance\Models\FinanceSequence;
use Carbon\CarbonImmutable;

final class GaplessSequenceService
{
    /**
     * Mints the next contiguous, gapless document number under row-level lock.
     */
    public function nextNumber(string $tenantId, string $prefix, ?int $year = null): string
    {
        $targetYear = $year ?? (int) CarbonImmutable::now('UTC')->format('Y');
        $sequenceKey = "{$prefix}-{$targetYear}";

        $sequence = FinanceSequence::query()
            ->where('tenant_id', $tenantId)
            ->where('sequence_key', $sequenceKey)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            $sequence = new FinanceSequence();
            $sequence->forceFill([
                'tenant_id' => $tenantId,
                'sequence_key' => $sequenceKey,
                'prefix' => $prefix,
                'current_number' => 1,
                'year' => $targetYear,
            ]);
            $sequence->save();

            $nextVal = 1;
        } else {
            $sequence->current_number += 1;
            $sequence->save();
            $nextVal = $sequence->current_number;
        }

        return sprintf('%s-%d-%06d', $prefix, $targetYear, $nextVal);
    }
}

