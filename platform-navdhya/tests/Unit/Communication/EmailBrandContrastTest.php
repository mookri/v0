<?php

namespace Tests\Unit\Communication;

use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM06-01 (Closes C-12 / Gate G-MAIL-1):
 * Mathematically calculates WCAG relative luminance and contrast ratios for Sacred Ember brand tokens,
 * and asserts that email templates contain strictly zero green tokens.
 */
final class EmailBrandContrastTest extends TenantIntegrationTestCase
{
    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255.0;
        $g = hexdec(substr($hex, 2, 2)) / 255.0;
        $b = hexdec(substr($hex, 4, 2)) / 255.0;

        $r = $r <= 0.03928 ? $r / 12.92 : (($r + 0.055) / 1.055) ** 2.4;
        $g = $g <= 0.03928 ? $g / 12.92 : (($g + 0.055) / 1.055) ** 2.4;
        $b = $b <= 0.03928 ? $b / 12.92 : (($b + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function contrastRatio(string $hex1, string $hex2): float
    {
        $l1 = $this->luminance($hex1);
        $l2 = $this->luminance($hex2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public function test_calculated_contrast_ratios_match_wcag_and_spec(): void
    {
        // 1. Aubergine #2C2033 on Warm Ivory #FFF7ED: 14.53:1 (AAA)
        $aubergineOnIvory = $this->contrastRatio('#2C2033', '#FFF7ED');
        $this->assertGreaterThanOrEqual(14.0, $aubergineOnIvory);
        $this->assertGreaterThanOrEqual(4.5, $aubergineOnIvory); // Passes AA
        $this->assertGreaterThanOrEqual(7.0, $aubergineOnIvory); // Passes AAA

        // 2. Aubergine #2C2033 on Navdhya Flame #F16A24: 5.01:1 (AA)
        $aubergineOnFlame = $this->contrastRatio('#2C2033', '#F16A24');
        $this->assertGreaterThanOrEqual(4.5, $aubergineOnFlame); // Passes AA for CTA buttons

        // 3. Flame #F16A24 on Warm Ivory #FFF7ED: 2.90:1 (Fails AA for normal text!)
        $flameOnIvory = $this->contrastRatio('#F16A24', '#FFF7ED');
        $this->assertLessThan(4.5, $flameOnIvory); // Confirmed failure — strictly forbidden for body copy
    }

    public function test_rendered_email_template_contains_zero_green_tokens(): void
    {
        $view = view('mail.notification-email', [
            'title' => 'Puja Confirmed',
            'body' => 'Your ritual ceremony is scheduled.',
            'locale' => 'en',
        ])->render();

        // Check for green keywords or hex codes
        $forbiddenGreens = [
            'green',
            'emerald',
            'lime',
            'teal',
            '#10b981',
            '#22c55e',
            '#84cc16',
            '#14b8a6',
            '#059669',
            '#15803d',
        ];

        foreach ($forbiddenGreens as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase(
                $forbidden,
                $view,
                "Rendered email contains forbidden green brand token: {$forbidden}"
            );
        }

        // Verify Warm Ivory canvas and Aubergine Ink text
        $this->assertStringContainsString('#FFF7ED', $view);
        $this->assertStringContainsString('#2C2033', $view);
    }
}

