<?php

namespace App\Platform\Identity\OAuth\Http\Middleware;

use App\Platform\Identity\OAuth\IssuerIdentifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RFC 9207: every redirect-based authorization response — success or
 * error — carries the issuer identifier as an `iss` query parameter,
 * so the client can bind the response to the authorization server it
 * initiated the flow with (response mix-up defence). Appended only
 * when the top-level query actually carries a protocol outcome
 * (`code` or `error`): the federation login bounce (a `redirect`
 * query only) is untouched. Attached to all three authorize routes
 * (GET/POST/DELETE) — errors from consent denial ride the same
 * redirect seam.
 */
final class AppendsIssuerToAuthorizationResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->isRedirect()) {
            return $response;
        }

        $location = (string) $response->headers->get('Location', '');

        if ($location === '') {
            return $response;
        }

        $fragmentPosition = strpos($location, '#');
        $fragment = '';

        if ($fragmentPosition !== false) {
            $fragment = substr($location, $fragmentPosition);
            $location = substr($location, 0, $fragmentPosition);
        }

        $parts = explode('?', $location, 2);
        $query = $parts[1] ?? '';
        parse_str($query, $params);

        // Only protocol outcomes are issuer-bound responses; the login
        // bounce (redirect key only) stays exactly as issued.
        if (! array_key_exists('code', $params) && ! array_key_exists('error', $params)) {
            return $response;
        }

        if (! array_key_exists('iss', $params)) {
            $params['iss'] = IssuerIdentifier::value();
            $location = $parts[0].'?'.http_build_query($params);
        }

        $response->headers->set('Location', $location.$fragment);

        return $response;
    }
}
