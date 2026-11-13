<?php

namespace Tests\Feature\Calendar;

use App\Domain\Calendar\Festivals\FestivalRuleRegistry;
use PHPUnit\Framework\TestCase;

class PilotFestivalRulesTest extends TestCase
{
    public function test_holika_dahan_and_rangwali_holi_are_distinct_consecutive_days_gate_g_fest_1(): void
    {
        $holika = FestivalRuleRegistry::getRule('HOLIKA_DAHAN');
        $rangwali = FestivalRuleRegistry::getRule('RANGWALI_HOLI');

        $this->assertNotNull($holika);
        $this->assertNotNull($rangwali);

        // Holika Dahan is on Phalguna Purnima (Tithi 15) during Pradosha
        $this->assertSame(15, $holika['tithi_target']);
        $this->assertSame('PRADOSHA', $holika['anchor']);
        $this->assertTrue($holika['exclude_bhadra']);

        // Rangwali Holi is on Chaitra Krishna Pratipada (Tithi 16) at Sunrise
        $this->assertSame(16, $rangwali['tithi_target']);
        $this->assertSame('SUNRISE', $rangwali['anchor']);

        // They are consecutive tithis
        $this->assertSame($holika['tithi_target'] + 1, $rangwali['tithi_target']);
    }

    public function test_scripture_provenance_attached_to_all_pilot_rules(): void
    {
        foreach (FestivalRuleRegistry::PILOT_FESTIVALS as $key => $rule) {
            $this->assertNotEmpty($rule['name'], "Rule {$key} must have name");
            $this->assertNotEmpty($rule['scripture'], "Rule {$key} must have scripture citation");
            $this->assertNotEmpty($rule['rule_summary'], "Rule {$key} must have plain-language summary");
        }
    }
}

