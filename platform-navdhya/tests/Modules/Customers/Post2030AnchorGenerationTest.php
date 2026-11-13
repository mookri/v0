<?php

namespace Tests\Modules\Customers;

use App\Domain\Calendar\Panchang\AnchorOccurrenceResolver;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Validates post-2030 horizon continuity for ritual reminders (T-JYAUT-00c / D-13).
 * Proves that occurrence resolution for 2035 CE succeeds without hardcoded year exceptions.
 */
final class Post2030AnchorGenerationTest extends TestCase
{
    public function test_anchor_occurrence_resolves_successfully_in_year_2035(): void
    {
        $resolver = new AnchorOccurrenceResolver();
        $baseline = CarbonImmutable::parse('2035-01-01 00:00:00', 'Asia/Kolkata');

        $rule = [
            'type' => 'CUSTOM_TITHI',
            'index_in_paksha' => 11, // Ekadashi
            'tradition' => 'PURNIMANTA',
        ];

        $occurrence = $resolver->occurrenceForAnchor($rule, $baseline, 'Asia/Kolkata', 'PURNIMANTA');

        $this->assertNotNull($occurrence);
        $this->assertStringStartsWith('2035-', $occurrence->date);
        $this->assertSame(11, $occurrence->tithi['index_in_paksha']);
    }
}

