<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Contracts;

/**
 * Deptrac-blessed read port exposing confident feedback aggregates and
 * methodological disclosures to Search, Pandit, and Booking without model coupling.
 */
interface ReadsFeedbackQuality
{
    /**
     * @return array{
     *   subject_type: string,
     *   subject_id: string,
     *   sample_count: int,
     *   confidence_tier: string,
     *   confidence_label: string,
     *   bayesian_average: float|null,
     *   lifetime_average: float|null,
     *   recent_average: float|null,
     *   punctuality_wilson_lower: float|null,
     *   prior_version: string|null,
     * }|null
     */
    public function summaryForSubject(string $subjectType, string $subjectId): ?array;

    public function hasActiveSafetySuppression(string $bookingId): bool;

    /**
     * Summary of submitted review for closed-loop support dossiers (TS-06 / E-TS-08).
     *
     * @return array{review_reference: string, overall_rating: int, subject_type: string, subject_id: string, submitted_at: string|null}|null
     */
    public function reviewSummaryForBooking(string $bookingId): ?array;
}

