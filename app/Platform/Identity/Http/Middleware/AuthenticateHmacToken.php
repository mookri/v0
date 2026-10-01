<?php

namespace App\Platform\Identity\Http\Middleware;

use App\Models\User;
use App\Platform\Identity\ActorTokenVerifier;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session-or-HMAC authentication (token bridge): session clients pass
 * through untouched; otherwise frontend-minted bearer tokens are verified
 * (ActorTokenVerifier) and linked via external_id. Denies with the same
 * AuthenticationException as `auth` when neither succeeds, so routes use
 * this INSTEAD of `auth` (Laravel runs `auth` before custom aliases
 * regardless of listing order — stacking them breaks token clients).
 * Unknown external ids fail closed (no auto-provisioning).
 */
final class AuthenticateHmacToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            return $next($request);
        }

        $header = $request->header('Authorization', '');
        if (is_string($header) && str_starts_with($header, 'Bearer ')) {
            $secret = (string) config('identity.hmac_secret', '');
            $actor = ActorTokenVerifier::verify($secret, substr($header, 7), gmdate('Y-m-d\TH:i:s.v\Z'));

            if ($actor !== null) {
                $user = User::query()->where('external_id', $actor['user_id'])->first();
                if ($user !== null) {
                    $request->attributes->set('actor', $actor);
                    $this->guard()->setUser($user);

                    return $next($request);
                }
            }
        }

        throw new AuthenticationException('Unauthenticated.');
    }

    private function guard(): \Illuminate\Contracts\Auth\StatefulGuard
    {
        /** @var \Illuminate\Contracts\Auth\StatefulGuard $guard */
        $guard = auth()->guard();

        return $guard;
    }
}
