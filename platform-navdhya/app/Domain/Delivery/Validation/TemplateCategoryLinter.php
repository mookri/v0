<?php

namespace App\Domain\Delivery\Validation;

use App\Domain\Delivery\Models\CommunicationTemplate;

/**
 * Category correctness and marketing leakage linter (E-CM-04 / §9.2 / Task T-CM04-02).
 * Strictly forbids promotional and marketing terms in UTILITY templates
 * to protect WhatsApp Business quality rating and prevent template rejection.
 */
final class TemplateCategoryLinter
{
    private const MARKETING_KEYWORDS = [
        'discount',
        'sale',
        'offer',
        'deal',
        'cashback',
        'coupon',
        'promo',
        'save %',
        'free gift',
        'limited time',
        'special price',
        'buy now',
    ];

    /**
     * @return array{valid: bool, violations: list<string>}
     */
    public function lint(string $category, string $content): array
    {
        $category = strtoupper(trim($category));
        $lowerContent = strtolower($content);
        $violations = [];

        if (in_array($category, [CommunicationTemplate::CATEGORY_UTILITY, CommunicationTemplate::CATEGORY_AUTHENTICATION], true)) {
            foreach (self::MARKETING_KEYWORDS as $keyword) {
                if (str_contains($lowerContent, $keyword)) {
                    $violations[] = "Marketing keyword [{$keyword}] forbidden in {$category} template.";
                }
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
        ];
    }

    public function isDispatchAllowed(CommunicationTemplate $template): bool
    {
        return $template->approval_status === CommunicationTemplate::STATUS_APPROVED;
    }
}

