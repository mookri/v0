<?php

namespace App\Domain\Verification\Risk;

use App\Domain\Verification\Identity\NameMatcher;

/**
 * Sanctions & PEP screening service locked unconditionally behind G-LEGAL-0 (T-TR07-07).
 * Never emits an auto-rejection outcome; possible matches enter human adjudication queue.
 */
final class SanctionsAndPepScreeningService
{
    /** @var list<array{list: string, name: string, dob: string|null, ref: string}> */
    private const WATCHLIST_FIXTURES = [
        ['list' => 'UNSC_1267', 'name' => 'Designated Test Entity One', 'dob' => '1970-01-01', 'ref' => 'UNSC-TEST-01'],
        ['list' => 'IN_UAPA_SCHEDULE', 'name' => 'Designated India Schedule Entity', 'dob' => null, 'ref' => 'MHA-TEST-02'],
    ];

    public function __construct(
        private readonly RegulatoryApplicabilityGate $gate,
        private readonly NameMatcher $matcher,
    ) {
    }

    /**
     * @param  list<array{list: string, name: string, dob?: string|null, ref: string}>|null  $customWatchlist
     * @return array{outcome: string, possible_matches: list<array<string, mixed>>, human_review_required: bool}
     */
    public function screen(
        string $fullName,
        ?string $dateOfBirth = null,
        ?array $customWatchlist = null,
    ): array {
        // Hard §20 R-07 enforcement: throws RegulatoryGateLockedException until counsel clearance clears.
        $this->gate->assertAmlEnabled();

        $list = $customWatchlist ?? self::WATCHLIST_FIXTURES;
        $matches = [];

        foreach ($list as $entry) {
            $res = $this->matcher->compare($fullName, $entry['name']);
            $dobMatched = $dateOfBirth !== null && isset($entry['dob']) && $entry['dob'] === $dateOfBirth;

            if ($res->score >= 0.80 || $dobMatched) {
                // POSSIBLE_MATCH never auto-promotes to CONFIRMED_MATCH or auto-rejects.
                $matches[] = [
                    'list' => $entry['list'],
                    'ref' => $entry['ref'],
                    'name_score' => $res->score,
                    'dob_corroborated' => $dobMatched,
                    'status' => 'POSSIBLE_MATCH',
                ];
            }
        }

        return [
            'outcome' => $matches === [] ? 'CLEAR' : 'POSSIBLE_MATCH',
            'possible_matches' => $matches,
            'human_review_required' => $matches !== [],
        ];
    }
}

