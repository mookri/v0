<?php

namespace App\Platform\Identity\Otp;

/**
 * Audience -> provider-name routing (Verification Fabric S48). The
 * name is resolved from current config at call time: an audience
 * selects 'local' (default) or a remote provider name; remote
 * instances are registry-keyed <selected>-<audience> (S47 convention
 * — one Firebase project per audience), and 'local' is special-cased
 * by each service.
 */
final class OtpAudienceRouter
{
    public static function providerName(string $audience): string
    {
        $selected = (string) config(
            'otp-providers.audiences.'.$audience.'.provider',
            LocalCodeProvider::NAME,
        );

        return self::nameFor($audience, $selected);
    }

    public static function nameFor(string $audience, string $selected): string
    {
        if ($selected === '' || $selected === LocalCodeProvider::NAME) {
            return LocalCodeProvider::NAME;
        }

        return $selected.'-'.$audience;
    }
}
