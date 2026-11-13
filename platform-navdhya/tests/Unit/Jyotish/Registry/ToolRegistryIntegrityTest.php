<?php

namespace Tests\Unit\Jyotish\Registry;

use App\Domain\Jyotish\Registry\JyotishToolRegistry;
use PHPUnit\Framework\TestCase;

final class ToolRegistryIntegrityTest extends TestCase
{
    public function test_registry_contains_at_least_50_core_tools(): void
    {
        $tools = JyotishToolRegistry::TOOLS;

        $this->assertGreaterThanOrEqual(50, count($tools), 'Registry must define at least 50 core tools (JY-001 to JY-050).');
    }

    public function test_all_tools_declare_complete_metadata(): void
    {
        $allowedStatuses = ['pending', 'unverified', 'golden_tested', 'practitioner_reviewed', 'disputed'];

        foreach (JyotishToolRegistry::TOOLS as $id => $tool) {
            $this->assertNotEmpty($tool['display_name'], "Tool {$id} missing display_name");
            $this->assertNotEmpty($tool['category'], "Tool {$id} missing category");
            $this->assertNotEmpty($tool['family'], "Tool {$id} missing family");
            $this->assertNotEmpty($tool['methodology'], "Tool {$id} missing methodology");
            $this->assertNotEmpty($tool['spec_id'], "Tool {$id} missing spec_id");
            $this->assertNotEmpty($tool['input_schema'], "Tool {$id} missing input_schema");
            $this->assertNotEmpty($tool['output_schema'], "Tool {$id} missing output_schema");
            $this->assertNotEmpty($tool['permission'], "Tool {$id} missing permission");
            $this->assertNotEmpty($tool['ruleset_code'], "Tool {$id} missing ruleset_code");
            $this->assertNotEmpty($tool['source_reference'], "Tool {$id} missing source_reference");
            $this->assertContains(
                $tool['verification_status'],
                $allowedStatuses,
                "Tool {$id} has invalid verification_status {$tool['verification_status']}"
            );
        }
    }

    public function test_tool_lookup_by_id(): void
    {
        $d1 = JyotishToolRegistry::getTool('jyotish.chart.d1');

        $this->assertNotNull($d1);
        $this->assertSame('JY-005', $d1['spec_id']);
        $this->assertSame('Complete D1 Kundli', $d1['display_name']);
        $this->assertSame('parashari', $d1['methodology']);
    }
}

