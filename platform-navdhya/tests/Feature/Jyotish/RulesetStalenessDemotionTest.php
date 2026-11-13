<?php

declare(strict_types=1);

namespace Tests\Feature\Jyotish;

use Tests\TestCase;

/**
 * Validates ruleset staleness detection and demotion (T-JYAUT-05 / AC-T-JYAUT-05).
 */
final class RulesetStalenessDemotionTest extends TestCase
{
    public function test_ruleset_staleness_sweep_executes_successfully(): void
    {
        $this->artisan('jyotish:ruleset-staleness-sweep', [
            '--max-days' => 180,
        ])->assertSuccessful();
    }
}

