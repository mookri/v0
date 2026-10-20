<?php

namespace App\Platform\Identity\Otp;

/**
 * Identifier normalization for verification compares only. Stored
 * identifiers are never rewritten — `users.phone` keeps its e164
 * shape (`+9199…`) and `findUser` must keep matching it — but MSG91
 * returns bare digits (`9199…`), so every comparison collapses both
 * sides through this one rule: emails lowercase-trimmed, everything
 * else digits-only.
 */
final class Msg91Identifier
{
    public static function canonical(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            return strtolower(trim($identifier));
        }

        return (string) preg_replace('/\D/', '', $identifier);
    }
}
