<?php

declare(strict_types=1);

namespace App\Modules\Support\Domain\Grievance;

use App\Modules\Support\Models\TrustSlaPolicy;
use Carbon\CarbonImmutable;

/**
 * Config-backed statutory clock calculator (TS-11 / §10.3 / A-2 / A-3).
 *
 * Separates statutory thresholds (officer-approved config rows in
 * `trust_sla_policies`, never hardcoded engineering constants) from internal
 * queue urgency, with deterministic `calendar_month` end-of-month clamping.
 */
final class StatutoryClockService
{
    public const DEFAULT_ACK_MINUTES = 2880; // 48h config baseline pending counsel sign-off

    /**
     * Compute due timestamp using approved `trust_sla_policies` or safe default config.
     *
     * @return array{due_at: CarbonImmutable, policy_version: string}
     */
    public function computeDueAt(string $tenantId, string $metric, CarbonImmutable $intakeAt): array
    {
        $policy = TrustSlaPolicy::query()
            ->where('tenant_id', $tenantId)
            ->where('scope', 'statutory')
            ->where('metric', $metric)
            ->where('effective_from', '<=', $intakeAt->toDateTimeString())
            ->where(function ($q) use ($intakeAt): void {
                $q->whereNull('effective_until')->orWhere('effective_until', '>=', $intakeAt->toDateTimeString());
            })
            ->latest('effective_from')
            ->first();

        if ($policy !== null) {
            $due = $this->applyCalendarRule(
                $intakeAt,
                (int) $policy->duration_minutes,
                (string) $policy->calendar_handling,
            );

            return [
                'due_at' => $due,
                'policy_version' => (string) $policy->version,
            ];
        }

        if ($metric === 'redress') {
            return [
                'due_at' => $intakeAt->addMonthNoOverflow(),
                'policy_version' => 'default_pending_counsel_v1',
            ];
        }

        return [
            'due_at' => $intakeAt->addMinutes(self::DEFAULT_ACK_MINUTES),
            'policy_version' => 'default_pending_counsel_v1',
        ];
    }

    public function applyCalendarRule(CarbonImmutable $start, int $durationMinutes, string $handling): CarbonImmutable
    {
        return match ($handling) {
            'calendar_month' => $start->addMonthNoOverflow(),
            'business_hours' => $this->addBusinessMinutes($start, $durationMinutes),
            default => $start->addMinutes($durationMinutes),
        };
    }

    private function addBusinessMinutes(CarbonImmutable $start, int $minutes): CarbonImmutable
    {
        $cursor = $start;
        $remaining = max(0, $minutes);

        while ($remaining > 0) {
            $cursor = $cursor->addMinutes(60);
            if (! $cursor->isWeekend()) {
                $remaining -= 60;
            }
        }

        return $cursor;
    }
}

