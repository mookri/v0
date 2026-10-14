<?php

namespace App\Platform\Identity\Otp;

/**
 * Audience -> provider-name routing (Verification Fabric). The name is
 * resolved from current config at call time; the default everywhere is
 * the MSG91 widget. The selected value is the registry name itself
 * (one widget serves every audience) — swapping vendors is a new
 * adapter + a config flip, never a code change (ADR-002 recipe 1).
 */
final class OtpAudienceRouter
{
    public static function providerName(string $audience): string
    {
        $selected = (string) config(
            'otp-providers.audiences.'.$audience.'.provider',
            Msg91WidgetProvider::NAME,
        );

        return $selected === '' ? Msg91WidgetProvider::NAME : $selected;
    }
}
