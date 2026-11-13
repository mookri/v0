<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Services\ReasonCodeGenerator;
use PHPUnit\Framework\TestCase;

class ReasonCodeTest extends TestCase
{
    public function test_reason_code_generator_returns_verified_labels(): void
    {
        $gen = new ReasonCodeGenerator();

        $this->assertSame('Aligns with your family tradition', $gen->getLabel(ReasonCodeGenerator::SAME_TRADITION));
        $this->assertSame('Frequently booked in this category', $gen->getLabel(ReasonCodeGenerator::POPULAR_IN_CATEGORY));
        $this->assertSame('Popular among devotees in your city', $gen->getLabel(ReasonCodeGenerator::POPULAR_NEAR_YOU));
    }

    public function test_reason_code_generator_rejects_unverifiable_labels(): void
    {
        $gen = new ReasonCodeGenerator();

        $this->expectException(\InvalidArgumentException::class);
        $gen->getLabel('UNVERIFIED_AI_HUNCH');
    }

    public function test_reason_code_generator_evaluates_precedence(): void
    {
        $gen = new ReasonCodeGenerator();

        $reason1 = $gen->determineReason(['same_tradition' => true, 'city_matched' => true]);
        $this->assertSame(ReasonCodeGenerator::SAME_TRADITION, $reason1);

        $reason2 = $gen->determineReason(['city_matched' => true]);
        $this->assertSame(ReasonCodeGenerator::POPULAR_NEAR_YOU, $reason2);
    }
}

