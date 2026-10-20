<?php

namespace App\Platform\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session context for API reads (doc 06 §8.4): interactive admin-shell
 * components fetch JSON from module routes with the web session's user.
 *
 * Scope is deliberately narrow so the CSRF posture of the API does not
 * move: only safe methods (GET/HEAD) ever start a session, and only for
 * requests that actually carry a session cookie. Unsafe methods and
 * cookie-less (Bearer/machine) clients skip this entirely, leaving
 * their behavior byte-identical — mutations remain token-only, and web
 * form mutations keep VerifyCsrfToken on the shared web actions.
 *
 * Cookie handling mirrors the web group's EncryptCookies recipe so one
 * browser cookie works on both surfaces: values are decrypted only when
 * they actually decrypt (raw TLS-only ids issued by the platform API's
 * own StartSession keep working, per the identity sessions trade-off),
 * and the response session cookie is re-encrypted so a subsequent web
 * request stays authenticated.
 */
final class SessionContextForReads
{
    public function __construct(private readonly Encrypter $encrypter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $name = (string) config('session.cookie');
        $cookie = $request->cookies->get($name);

        if (! is_string($cookie) || $cookie === '') {
            // Token-style clients carry no session cookie: untouched.
            return $next($request);
        }

        if (($plain = $this->decryptSessionCookie($name, $cookie)) !== null) {
            $request->cookies->set($name, $plain);
        }

        $response = app(StartSession::class)->handle($request, $next);

        return $this->encryptSessionCookie($response, $name);
    }

    /**
     * Decrypt one cookie with the exact web-group recipe, falling back to
     * the raw value when it is not an encrypted cookie at all (fail-safe:
     * an unknown id makes StartSession mint a fresh anonymous session).
     */
    private function decryptSessionCookie(string $name, string $cookie): ?string
    {
        try {
            $plain = $this->encrypter->decrypt($cookie, false);
        } catch (DecryptException) {
            return null;
        }

        if (! is_string($plain)) {
            return null;
        }

        return CookieValuePrefix::validate($name, $plain, $this->encrypter->getAllKeys());
    }

    /**
     * Re-encrypt the session cookie on the response exactly like the web
     * group does, so the browser keeps one cookie both surfaces accept.
     */
    private function encryptSessionCookie(Response $response, string $name): Response
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() !== $name) {
                continue;
            }

            $response->headers->setCookie($cookie->withValue(
                $this->encrypter->encrypt(
                    CookieValuePrefix::create($name, $this->encrypter->getKey()).$cookie->getValue(),
                    false,
                )
            ));
        }

        return $response;
    }
}
