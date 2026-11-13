<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Domain\Anomaly;

use App\Modules\Feedback\Models\FeedbackAnomaly;
use App\Modules\Feedback\Models\FeedbackSubmission;
use Carbon\CarbonImmutable;

/**
 * Deterministic rule-first review integrity scanner (TS-09, §6.5).
 *
 * Output is ALWAYS a hold for human review + appealable notice — NEVER an
 * automated fraud determination against any person.
 */
final class ReviewIntegrityScanner
{
    public const RULE_BURST_VELOCITY = 'RULE_BURST_VELOCITY';
    public const RULE_DUPLICATE_TEXT = 'RULE_DUPLICATE_TEXT';
    public const RULE_SELF_REVIEW = 'RULE_SELF_REVIEW';
    public const RULE_INCENTIVIZED_LANGUAGE = 'RULE_INCENTIVIZED_LANGUAGE';

    /**
     * @return list<FeedbackAnomaly>
     */
    public function scan(
        string $tenantId,
        FeedbackSubmission $submission,
        ?int $subjectOwnerUserId = null,
    ): array {
        $detected = [];

        // 1. Self-review or same user account as assigned provider
        if ($subjectOwnerUserId !== null && (int) $submission->reviewer_user_id === $subjectOwnerUserId) {
            $detected[] = $this->recordAnomaly($tenantId, $submission, self::RULE_SELF_REVIEW, [
                'reviewer_user_id' => $submission->reviewer_user_id,
                'subject_id' => $submission->subject_id,
            ]);
        }

        // 2. Burst velocity (> 10 submissions for same subject within 1 hour)
        $since = CarbonImmutable::now('UTC')->subHour();
        $recentCount = FeedbackSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $submission->subject_type)
            ->where('subject_id', $submission->subject_id)
            ->where('id', '!=', (string) $submission->getKey())
            ->where('submitted_at', '>=', $since->toDateTimeString())
            ->count();

        if ($recentCount >= 10) {
            $detected[] = $this->recordAnomaly($tenantId, $submission, self::RULE_BURST_VELOCITY, [
                'recent_hour_count' => $recentCount,
            ]);
        }

        // 3. Exact/normalized duplicate text across other bookings
        $text = $submission->original_text !== null ? strtolower(trim(preg_replace('/\s+/', ' ', $submission->original_text) ?? '')) : '';
        if (strlen($text) >= 24) {
            $dup = FeedbackSubmission::query()
                ->where('tenant_id', $tenantId)
                ->where('id', '!=', (string) $submission->getKey())
                ->whereNotNull('original_text')
                ->get(['id', 'original_text'])
                ->first(function (FeedbackSubmission $other) use ($text): bool {
                    $otherNorm = strtolower(trim(preg_replace('/\s+/', ' ', (string) $other->original_text) ?? ''));

                    return $otherNorm === $text;
                });

            if ($dup !== null) {
                $detected[] = $this->recordAnomaly($tenantId, $submission, self::RULE_DUPLICATE_TEXT, [
                    'matched_submission_id' => (string) $dup->getKey(),
                ]);
            }
        }

        // 4. Incentivised review heuristic
        if ($text !== '' && preg_match('/\b(cashback for 5 star|refund if i rate 5|paid for review)\b/i', $text) === 1) {
            $detected[] = $this->recordAnomaly($tenantId, $submission, self::RULE_INCENTIVIZED_LANGUAGE, [
                'signal' => 'incentivized_phrase_match',
            ]);
        }

        return $detected;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordAnomaly(string $tenantId, FeedbackSubmission $submission, string $ruleId, array $payload): FeedbackAnomaly
    {
        /** @var FeedbackAnomaly */
        return FeedbackAnomaly::query()->create([
            'tenant_id' => $tenantId,
            'submission_id' => (string) $submission->getKey(),
            'rule_id' => $ruleId,
            'signal_payload' => $payload,
            'state' => FeedbackAnomaly::STATE_HELD,
        ]);
    }
}

