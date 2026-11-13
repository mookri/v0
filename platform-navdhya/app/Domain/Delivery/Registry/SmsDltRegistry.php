<?php

namespace App\Domain\Delivery\Registry;

use App\Domain\Delivery\Exceptions\DltNotRegisteredException;
use App\Domain\Delivery\Models\SmsDltTemplate;

/**
 * Telecom TRAI DLT template resolution and compliance registry (E-CM-05 / Task T-CM05-02 & T-CM05-03).
 * Eliminates unregistered fallback (C-11) and detects Devanagari Hindi Unicode.
 */
final class SmsDltRegistry
{
    public function resolveTemplate(string $templateCode): SmsDltTemplate
    {
        $template = SmsDltTemplate::query()
            ->where('template_code', $templateCode)
            ->where('approval_status', SmsDltTemplate::STATUS_APPROVED)
            ->first();

        if ($template === null) {
            throw new DltNotRegisteredException($templateCode);
        }

        return $template;
    }

    /**
     * Detects if content contains Devanagari / Indic Unicode codepoints (U+0900 to U+097F).
     */
    public function isUnicode(string $content): bool
    {
        return (bool) preg_match('/[\x{0900}-\x{097F}]/u', $content);
    }
}

