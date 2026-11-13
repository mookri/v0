<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Remedies;

use App\Domain\Jyotish\Remedies\RemedyKnowledgeBase;
use PHPUnit\Framework\TestCase;

/**
 * Validates ethical guardrails for Vedic remedies (T-JY10-06 / AC-T-JY10-06).
 */
final class RemedyEthicalGuardrailsTest extends TestCase
{
    public function test_gemstones_and_mantras_contain_mandatory_disclaimers(): void
    {
        $grahas = ['surya', 'chandra', 'shani'];

        foreach ($grahas as $graha) {
            $data = RemedyKnowledgeBase::forGraha($graha);

            $this->assertNotEmpty($data['mantra']);
            $this->assertNotEmpty($data['gemstone']['disclaimer']);
            $this->assertFalse($data['gemstone']['can_auto_purchase'], 'Remedies must never automatically inject products into cart');
            $this->assertTrue($data['ethical_guardrails']['fear_free'], 'Remedies must be fear-free');
            $this->assertTrue($data['ethical_guardrails']['no_commercial_trigger'], 'Remedies must not trigger commercial upsells');
        }
    }
}

