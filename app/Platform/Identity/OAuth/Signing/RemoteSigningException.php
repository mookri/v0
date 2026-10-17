<?php

namespace App\Platform\Identity\OAuth\Signing;

use RuntimeException;

/**
 * The KMS shim could not produce a signature: endpoint unreachable,
 * non-success response, or a payload that is not the documented
 * base64url RS256 signature. Raised mid-mint — league has no catch for
 * it, so the token exchange fails closed with a 500 and NO token
 * response, the same observable shape as any other mint-time failure.
 * A mint that cannot be signed through its custody never falls back to
 * local signing.
 */
final class RemoteSigningException extends RuntimeException
{
}
