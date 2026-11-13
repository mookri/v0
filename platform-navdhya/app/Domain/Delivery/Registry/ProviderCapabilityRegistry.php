<?php

namespace App\Domain\Delivery\Registry;

/**
 * Provider capability and channel feature registry (E-CM-03 / Task T-CM03-05).
 * Enforces that templates are only dispatched over channels capable of rendering them.
 */
final class ProviderCapabilityRegistry
{
    /**
     * @var array<string, list<string>>
     */
    private const CAPABILITIES = [
        'WHATSAPP' => [
            'interactive_cards',
            'quick_replies',
            'image_headers',
            'buttons',
            'rich_text',
            'pdf_attachments',
        ],
        'EMAIL' => [
            'html',
            'rich_text',
            'attachments',
            'multi_part',
            'preheader',
        ],
        'SMS' => [
            'text_only',
            'dlt_template_vars',
        ],
        'IN_APP' => [
            'rich_cards',
            'deep_links',
            'badge_counts',
        ],
    ];

    public function supportsFeature(string $channel, string $feature): bool
    {
        $channel = strtoupper(trim($channel));
        $caps = self::CAPABILITIES[$channel] ?? [];

        return in_array(strtolower(trim($feature)), $caps, true);
    }

    public function validateTemplateCompatibility(string $channel, string $templateType): bool
    {
        $channel = strtoupper(trim($channel));

        if ($templateType === 'interactive_card' && $channel === 'SMS') {
            return false; // SMS cannot render interactive cards
        }

        if ($templateType === 'pdf_attachment' && $channel === 'SMS') {
            return false; // SMS cannot render PDF attachments
        }

        return true;
    }
}

