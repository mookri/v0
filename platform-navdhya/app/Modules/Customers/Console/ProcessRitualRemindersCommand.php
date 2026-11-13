<?php

namespace App\Modules\Customers\Console;

use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Domain\Calendar\Contracts\ComputesPanchang;
use App\Domain\Calendar\Exceptions\OutOfCorpusException;
use App\Domain\Calendar\Panchang\PanchangOccurrence;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Modules\Customers\Outbox\FactAppender;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Daily ritual reminder daemon (E3):
 * Scans active reminders, recomputes astronomical occurrences deterministically from anchor rules,
 * caches daily calculations by anchor hash, and fires notifications with idempotent period guards.
 */
final class ProcessRitualRemindersCommand extends Command
{
    protected $signature = 'customers:process-reminders {--tenant= : Scope run to a single tenant}';

    protected $description = 'Scan active ritual reminders, recompute Panchang dates, and dispatch advance notifications.';

    public function handle(
        TenantContext $context,
        ConnectionResolver $connections,
        ComputesPanchang $panchangEngine,
        NotifiesCustomers $notifier,
        FactAppender $facts
    ): int {
        $tenantQuery = Tenant::query()->where('state', Tenant::STATE_ACTIVE);

        $specificTenant = $this->option('tenant');
        if (is_string($specificTenant) && $specificTenant !== '') {
            $tenantQuery->where('id', $specificTenant);
        }

        $tenants = $tenantQuery->get();
        $processedCount = 0;
        $firedCount = 0;

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->set($tenantId, null, null, $connections->cachedFor($tenant), [
                'system' => 'customers:process-reminders',
            ]);

            try {
                $reminders = CustomerRitualReminder::query()
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->get();

                $now = CarbonImmutable::now('UTC');

                /** @var CustomerRitualReminder $reminder */
                foreach ($reminders as $reminder) {
                    $processedCount++;
                    $rule = $reminder->anchor_rule;
                    if (! is_array($rule)) {
                        continue;
                    }

                    // 1. Resolve next astronomical occurrence with version-aware anchor hash caching (closes P-08)
                    $ephemerisVersion = config('calendar.ephemeris_version', 'meeus-truncated-v1');
                    $ayanamsaVersion = config('calendar.ayanamsa_version', 'lahiri-chitrapaksha-v1');
                    $rulesVersion = (int) ($rule['rules_version'] ?? config('calendar.rules_version', 2));
                    $anchorHash = md5(json_encode($rule));
                    $cacheKey = "panchang:{$tenantId}:{$now->toDateString()}:{$ephemerisVersion}:{$ayanamsaVersion}:{$rulesVersion}:{$anchorHash}";

                    try {
                        /** @var PanchangOccurrence $occurrence */
                        $occurrence = Cache::remember($cacheKey, 86400, function () use ($panchangEngine, $rule, $now): PanchangOccurrence {
                            return $panchangEngine->occurrenceForAnchor(
                                $rule,
                                $now->subDay(),
                                'Asia/Kolkata',
                                (string) ($rule['tradition'] ?? 'PURNIMANTA')
                            );
                        });
                    } catch (OutOfCorpusException $e) {
                        Log::warning("Skipping reminder {$reminder->id} due to out-of-corpus horizon: {$e->getMessage()}");
                        continue;
                    }

                    $occurrenceDate = CarbonImmutable::parse($occurrence->date, 'Asia/Kolkata')->startOfDay();
                    $today = $now->setTimezone('Asia/Kolkata')->startOfDay();
                    $daysUntil = (int) $today->diffInDays($occurrenceDate, false);

                    // Re-sync operational cursor
                    if ($reminder->next_occurrence_at?->toDateString() !== $occurrenceDate->toDateString()) {
                        $reminder->forceFill(['next_occurrence_at' => $occurrenceDate->toDateTimeString()])->save();
                    }

                    // 2. Check if today matches any configured advance alert slots
                    $advanceDays = $reminder->advance_days ?? [7, 3, 1, 0];
                    if ($daysUntil >= 0 && in_array($daysUntil, $advanceDays, true)) {
                        // Unique period dedup key: {reminder_id}:{year}-{masa}-{paksha}-{tithi}:{daysUntil}d
                        $periodKey = sprintf(
                            '%s:%s-%s-%s-%02d:%dd',
                            $reminder->id,
                            $occurrenceDate->year,
                            $occurrence->resolvedMasa(),
                            $occurrence->tithi['paksha'],
                            $occurrence->tithi['index_in_paksha'],
                            $daysUntil
                        );

                        if ($reminder->last_fired_period === $periodKey) {
                            // Already fired for this exact period and slot
                            continue;
                        }

                        // Fire notification through NotifiesCustomers port
                        $slotDesc = $daysUntil === 0 ? 'today' : "in {$daysUntil} day" . ($daysUntil > 1 ? 's' : '');
                        $body = "Sacred reminder: {$reminder->title} occurs {$slotDesc} ({$occurrence->resolvedMasa()} {$occurrence->tithi['name']}, {$occurrenceDate->format('d M Y')}).";

                        $notifier->notify(
                            tenantId: $tenantId,
                            userId: (string) $reminder->user_id,
                            kind: 'reminder',
                            title: $reminder->title,
                            body: $body,
                            eventId: $periodKey
                        );

                        $reminder->forceFill(['last_fired_period' => $periodKey])->save();

                        // Emit transactional outbox fact with explicit rules_version (T-JYAUT-00d)
                        $facts->append('customers.reminder.fired', $reminder->user_id, [
                            'reminder_id' => (string) $reminder->getKey(),
                            'occurrence_date' => $occurrence->date,
                            'days_until' => $daysUntil,
                            'period_key' => $periodKey,
                            'rules_version' => $rulesVersion,
                        ], $tenantId);

                        $firedCount++;
                    }
                }
            } finally {
                $context->clear();
            }
        }

        $this->info("Processed {$processedCount} reminders across active tenants. Fired {$firedCount} advance notifications.");

        return self::SUCCESS;
    }
}
