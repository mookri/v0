<?php

namespace App\Platform\Identity\OAuth\Dpop;

/**
 * A rejected DPoP proof (A-SEC-004-9, RFC 9449) with a stable,
 * loggable reason. DpopProof::validate never returns a partial result:
 * it either yields the JWK thumbprint or throws THIS — callers shape
 * the denial (league 400 `invalid_dpop_proof` JSON at the token
 * endpoint, envelope 401 + `WWW-Authenticate` at resource endpoints).
 */
final class DpopProofInvalidException extends \RuntimeException
{
}
