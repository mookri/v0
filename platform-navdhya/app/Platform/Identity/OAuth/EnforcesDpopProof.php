<?php

namespace App\Platform\Identity\OAuth;

use App\Platform\Identity\OAuth\Dpop\DpopProof;
use App\Platform\Identity\OAuth\Dpop\DpopProofInvalidException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The DPoP seams at the token endpoint (A-SEC-004-9, RFC 9449), shared
 * by OidcAuthCodeGrant (bind at exchange) and RecoverableRefreshTokenGrant
 * (re-prove at refresh) so the proof rules and the mandate denial cannot
 * drift between the two interactive grants:
 *
 *  - proof present → validated here (htm/htu of THIS endpoint, iat
 *    window, single-use jti, signature over the inline JWK); the
 *    returned thumbprint becomes the mint's binding,
 *  - proof absent + require_dpop profile → league 400
 *    `invalid_dpop_proof` (a mandate client never falls back to an
 *    unconstrained token),
 *  - proof absent + no mandate → null: the unbound mint path, byte
 *    unchanged (gradual adoption — the binding decides at resource
 *    time, never the client's word).
 *
 * Machine grants are NOT satisfied by this trait — CheckOAuthGrant
 * denies them outright for a mandated client (fail closed) until a
 * DPoP binding seam lands for those grants too.
 */
trait EnforcesDpopProof
{
    /**
     * @throws OAuthServerException league-shaped 400 invalid_dpop_proof
     */
    protected function dpopJktForTokenRequest(ServerRequestInterface $request, string $clientId): ?string
    {
        $proof = $request->getHeaderLine('DPoP');

        if ($proof === '') {
            if ($this->dpopMandatedFor($clientId)) {
                throw self::invalidDpopProof(
                    'This client requires DPoP-bound tokens (require_dpop) and the request carried no DPoP proof.'
                );
            }

            return null;
        }

        try {
            return DpopProof::validate(
                $proof,
                $request->getMethod(),
                (string) $request->getUri(),
                null
            );
        } catch (DpopProofInvalidException $e) {
            throw self::invalidDpopProof($e->getMessage());
        }
    }

    /**
     * The require_dpop mandate for this client (fail closed the other
     * way too: no profile row means no mandate — CheckOAuthGrant has
     * already denied unknown clients before any grant runs).
     */
    private function dpopMandatedFor(string $clientId): bool
    {
        $profile = OAuthClientProfile::query()
            ->where('client_id', $clientId)
            ->first();

        return $profile !== null && (bool) $profile->require_dpop;
    }

    /**
     * The reason lands in error_description (league's payload maps the
     * message there); hint stays the standard pointer.
     */
    private static function invalidDpopProof(string $description): OAuthServerException
    {
        return new OAuthServerException(
            $description,
            0,
            'invalid_dpop_proof',
            400,
            'Check the `DPoP` header parameter'
        );
    }
}
