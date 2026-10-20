<?php

namespace App\Platform\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every session cookie that leaves the process is encrypted.
 *
 * The web group encrypts its cookies with EncryptCookies. The API
 * surface does not: `SessionContextForReads` decrypts the incoming
 * cookie and re-encrypts the one it re-issues, but only for safe
 * methods. A second, outer `StartSession` (mounted group-wide on the
 * platform API) finished last and wrote the raw session id, so the
 * browser ended up holding a **plaintext bearer credential** for the
 * platform surface - and the web group could no longer decrypt the
 * cookie, silently destroying the web session.
 *
 * This middleware is the outer invariant: whatever wrote the cookie,
 * a raw session id never leaves the process. A cookie that is already
 * encrypted is left untouched, so there is no double encryption.
 */
final class EncryptsSessionCookie
{
    public function __construct(private readonly Encrypter $encrypter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $name = (string) config('session.cookie');

        if ($name === '') {
            return $response;
        }

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() !== $name) {
                continue;
            }

            $value = (string) $cookie->getValue();

            if ($value === '' || $this->isEncrypted($name, $value)) {
                continue;
            }

            $response->headers->setCookie($cookie->withValue(
                $this->encrypter->encrypt(
                    CookieValuePrefix::create($name, $this->encrypter->getKey()).$value,
                    false,
                )
            ));
        }

        return $response;
    }

    /**
     * Is this cookie already an encrypted session cookie?
     *
     * The check mirrors EncryptCookies exactly: the prefix lives INSIDE
     * the encrypted payload, so the value is decrypted first and the
     * prefix validated afterwards. A cookie that does not decrypt is a
     * raw session id and must be encrypted.
     */
    private function isEncrypted(string $name, string $value): bool
    {
        try {
            $plain = $this->encrypter->decrypt($value, false);
        } catch (DecryptException) {
            return false;
        }

        return CookieValuePrefix::validate($name, $plain, $this->encrypter->getAllKeys()) !== null;
    }
}
