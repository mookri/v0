<?php

namespace App\Modules\Customers\Auth;

use App\Modules\Customers\Contracts\VerifiesPhoneIdTokens;
use App\Platform\Identity\Otp\FirebaseServiceAccount;
use App\Platform\Identity\Otp\OtpProviderConfigurationException;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Factory;
use Lcobucci\JWT\UnencryptedToken;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * kreait-backed implementation of the phone-proof seam: verifies the ID * token the Firebase client SDK minted after the SMS round-trip and * extracts only the three claims the exchange may trust.
 *
 * Credentials come from the SAME env value the Verification Fabric
 * already reads (FIREBASE_CUSTOMER_SERVICE_ACCOUNT via the otp-providers
 * registry, path or inline JSON) through FirebaseServiceAccount, so
 * there is one source of truth and no second config file; kreait's own * container services stay unused. A filesystem cache pool keeps Google's * x509 keyset and the service-account OAuth token warm, so a login costs * one Google round-trip (the revocation lookup) instead of three.
 *
 * Log discipline (never tokens, phones, or identities):
 *  - Unavailable messages are symbolic-only (exception class + a *    connectivity category) — kreait's free text is classified, then *    discarded, never chained.
 *  - Rejected exceptions are never reported at all (a rejected token is *    a normal auth outcome), so they may keep their chain for debugging.
 */
final class FirebasePhoneIdTokenVerifier implements VerifiesPhoneIdTokens
{
    /** Tolerated exp/nbf clock skew on the JWT itself. */
    private const LEEWAY_SECONDS = 30;

    public function verify(string $idToken): array
    {
        $configured = (string) config('otp-providers.audiences.customer.firebase.service_account', '');
        $expectedProject = (string) config('otp-providers.audiences.customer.firebase.project', '');

        $serviceAccount = $this->resolveCredentials($configured);

        if ($expectedProject !== '' && $serviceAccount->projectId !== $expectedProject) {
            // Cross-project credentials fail closed HERE with a clear * // typed503, instead of letting every exchange surface a * confusing audience 401 from deep inside kreait.
            throw new FirebaseIdTokenUnavailableException('firebase service account project mismatch');
        }

        return $this->claims(
            $this->verifyWithKreait($configured, $idToken)
        );
    }

    private function resolveCredentials(string $configured): FirebaseServiceAccount
    {
        try {
            $serviceAccount = FirebaseServiceAccount::resolve($configured, 'customer');
        } catch (OtpProviderConfigurationException $exception) {
            // This exception's messages name the problem and the path * // while withholding file contents (documented contract), so * // chaining it into the report is safe by design — unlike the * // kreait free text below, which never travels.
            throw new FirebaseIdTokenUnavailableException('firebase service account unusable: '.$exception->getMessage(), 0, $exception);
        }

        if ($serviceAccount === null) {
            throw new FirebaseIdTokenUnavailableException('firebase service account not configured');
        }

        return $serviceAccount;
    }

    private function verifyWithKreait(string $configured, string $idToken): UnencryptedToken
    {
        try {
            return $this->auth($configured)->verifyIdToken(
                $idToken,
                checkIfRevoked: true,
                leewayInSeconds: self::LEEWAY_SECONDS,
            );
        } catch (RevokedIdToken $exception) {
            // Never reported (rejected is a normal outcome), so the * // chain stays for debugging without log exposure.
            throw new FirebaseIdTokenRejectedException('id token revoked', 0, $exception);
        } catch (FailedToVerifyToken $exception) {
            // kreait funnels BOTH claims failures (expired, bad signature, * // wrong audience) and the revocation lookup's failures (network, * // permission denied, deleted user) into this one class, so the * // message decides infrastructure-vs-credentials — and is then * // dropped: the thrown message is symbolic-only.
            if (self::looksLikeConnectivityFailure($exception->getMessage())) {
                throw new FirebaseIdTokenUnavailableException('firebase idtoken verify unavailable [FailedToVerifyToken/connectivity]');
            }

            throw new FirebaseIdTokenRejectedException('id token invalid', 0, $exception);
        } catch (\Throwable $exception) {
            // ApiConnectionFailed and anything else unexpected: * // infrastructure shape, typed as unavailable, symbolic message * // (class name) only.
            throw new FirebaseIdTokenUnavailableException('firebase idtoken verify unavailable ['.$exception::class.']');
        }
    }

    /**
     * Classification only — the message never reaches a log, so free text,
     * PII, or token material cannot leak through this path.
     */
    private static function looksLikeConnectivityFailure(string $message): bool
    {
        return preg_match(
            '/cURL|timed\s*out|timeout|connection|could not resolve|getaddrinfo|ssl|handshake|dns|network|permission denied|unavailable/i',
            $message
        ) === 1;
    }

    private function auth(string $configured): Auth
    {
        return (new Factory())
            ->withServiceAccount($configured)
            ->withDefaultCache(new FilesystemAdapter(
                'firebase-idtoken',
                3600,
                storage_path('framework/cache/firebase'),
            ))
            ->createAuth();
    }

    /**
     * @return array{phone: string, provider: string, auth_time: int}
     */
    private function claims(UnencryptedToken $verified): array
    {
        $claims = $verified->claims();

        $phone = $claims->get('phone_number');
        $authTime = $claims->get('auth_time');

        $firebase = $claims->get('firebase');
        $provider = null;
        if (is_array($firebase)) {
            $provider = $firebase['sign_in_provider'] ?? null;
        } elseif (is_object($firebase)) {
            $provider = $firebase->sign_in_provider ?? null;
        }

        // Phone sign-in tokens always carry a canonical +E.164 phone; a * // token without one cannot bind to an intent, so it is rejected * // as a credential failure (never as infrastructure).
        if (! is_string($phone) || preg_match('/^\+[1-9]\d{6,14}$/', $phone) !== 1) {
            throw new FirebaseIdTokenRejectedException('id token has no usable phone_number claim');
        }

        if (! is_string($provider) || $provider === '') {
            throw new FirebaseIdTokenRejectedException('id token has no sign-in provider claim');
        }

        if (! is_numeric($authTime)) {
            throw new FirebaseIdTokenRejectedException('id token has no auth_time claim');
        }

        return ['phone' => $phone, 'provider' => $provider, 'auth_time' => (int) $authTime];
    }
}
