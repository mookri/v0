<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Models\CommunicationTemplate;
use App\Domain\Delivery\Validation\TemplateCategoryLinter;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM04-02:
 * Proves that TemplateCategoryLinter rejects marketing leakages inside UTILITY templates
 * and enforces template pause propagation.
 */
final class TemplateCategoryLinterTest extends TenantIntegrationTestCase
{
    public function test_linter_rejects_promotional_terms_in_utility_templates(): void
    {
        $linter = new TemplateCategoryLinter();

        // 1. Valid utility template passes
        $cleanContent = 'Your Satyanarayan Puja is scheduled for {{date}} at {{time}}. Pandit ji will arrive 30 mins prior.';
        $cleanResult = $linter->lint('UTILITY', $cleanContent);
        $this->assertTrue($cleanResult['valid']);
        $this->assertEmpty($cleanResult['violations']);

        // 2. Polluted utility template containing "discount" or "offer" fails
        $pollutedContent = 'Your booking is confirmed! Get 20% discount and a special offer on your next havan.';
        $pollutedResult = $linter->lint('UTILITY', $pollutedContent);
        $this->assertFalse($pollutedResult['valid']);
        $this->assertCount(2, $pollutedResult['violations']);
    }

    public function test_paused_template_blocks_dispatch(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $template = CommunicationTemplate::query()->create([
            'code' => 'bkg_confirmed_paused_test',
            'category' => 'UTILITY',
            'meta_template_id' => 'tmpl_meta_99988',
            'approval_status' => CommunicationTemplate::STATUS_PAUSED,
            'content' => 'Your ritual is confirmed.',
        ]);

        $linter = new TemplateCategoryLinter();
        $this->assertFalse($linter->isDispatchAllowed($template));

        $template->approval_status = CommunicationTemplate::STATUS_APPROVED;
        $template->save();
        $this->assertTrue($linter->isDispatchAllowed($template));
    }
}

