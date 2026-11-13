<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Remedies;

/**
 * Vedic Upaya and Scriptural Remedial Knowledge Base (T-JY10-06).
 *
 * Strict Ethical Guardrails:
 * 1. Non-predictive and educational only.
 * 2. Zero fear-based upsell or fatalistic framing.
 * 3. Zero automated commercial triggers / cart injections for remedies.
 * 4. Mandatory medical and financial disclaimers on gemstone and mantra references.
 */
final readonly class RemedyKnowledgeBase
{
    public const MANDATORY_DISCLAIMER = 'Educational and spiritual reference only. Vedic remedies are contemplative traditions and do not substitute for professional medical, legal, or financial counsel. Navdhya makes no definitive guarantees of health cures or financial windfalls.';

    /**
     * @return array<string, mixed>
     */
    public static function forGraha(string $graha): array
    {
        $grahaKey = strtolower($graha);

        $registry = [
            'surya' => [
                'graha' => 'Surya',
                'mantra' => 'ॐ ह्रां ह्रीं ह्रौं सः सूर्याय नमः',
                'deity' => 'Lord Shiva / Aditya',
                'gemstone' => [
                    'name' => 'Ruby (Manikya)',
                    'disclaimer' => self::MANDATORY_DISCLAIMER,
                    'can_auto_purchase' => false,
                ],
                'seva' => 'Surya Namaskar at sunrise, offering water (Arghya)',
                'ethical_guardrails' => [
                    'fear_free' => true,
                    'non_fatalistic' => true,
                    'no_commercial_trigger' => true,
                ],
            ],
            'chandra' => [
                'graha' => 'Chandra',
                'mantra' => 'ॐ श्रां श्रीं श्रौं सः चन्द्राय नमः',
                'deity' => 'Goddess Parvati',
                'gemstone' => [
                    'name' => 'Natural Pearl (Mukta)',
                    'disclaimer' => self::MANDATORY_DISCLAIMER,
                    'can_auto_purchase' => false,
                ],
                'seva' => 'Meditation, respecting motherly elders',
                'ethical_guardrails' => [
                    'fear_free' => true,
                    'non_fatalistic' => true,
                    'no_commercial_trigger' => true,
                ],
            ],
            'shani' => [
                'graha' => 'Shani',
                'mantra' => 'ॐ प्रां प्रीं प्रौं सः शनैश्चराय नमः',
                'deity' => 'Lord Hanuman / Yama',
                'gemstone' => [
                    'name' => 'Blue Sapphire (Neelam)',
                    'disclaimer' => self::MANDATORY_DISCLAIMER,
                    'can_auto_purchase' => false,
                ],
                'seva' => 'Service to the elderly, charity to laborers',
                'ethical_guardrails' => [
                    'fear_free' => true,
                    'non_fatalistic' => true,
                    'no_commercial_trigger' => true,
                ],
            ],
        ];

        return $registry[$grahaKey] ?? [
            'graha' => $graha,
            'mantra' => 'ॐ शान्तिः शान्तिः शान्तिः',
            'deity' => 'Ishta Devata',
            'gemstone' => [
                'name' => 'None',
                'disclaimer' => self::MANDATORY_DISCLAIMER,
                'can_auto_purchase' => false,
            ],
            'seva' => 'Dharma and self-inquiry',
            'ethical_guardrails' => [
                'fear_free' => true,
                'non_fatalistic' => true,
                'no_commercial_trigger' => true,
            ],
        ];
    }
}

