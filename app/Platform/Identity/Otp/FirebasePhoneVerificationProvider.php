<?php

namespace App\Platform\Identity\Otp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Firebase Identity Toolkit adapter (Verification Fabric S47, server-
 * driven — evidence and endpoint contracts in
 * docs/04-security/firebase-phone-otp-spike.md):
 *
 *   send   POST /v1/accounts:sendVerificationCode?key=<web api key>
 *          {phoneNumber, recaptchaToken?}        -> {sessionInfo}
 *   verify POST /v1/accounts:signInWithPhoneNumber?key=<web api key>
 *          {sessionInfo, code}                   -> {idToken, phoneNumber}
 *
 * One adapter instance = one Firebase project (customer and vendor
 * audiences sit on separate projects; the config registry names the
 * instances firebase-<audience>). The web API key authenticates both
 * endpoints; the service-account slot in config/otp-providers.php is
 * reserved for the optional Admin SDK idToken cross-check (spike
 * checklist item) and is not consumed here.
 *
 * Error taxonomy — normalized to the port's three outcomes, with the
 * spike L3 checklist meant to calibrate CODE_ERROR_CODES against the
 * live error codes before the production gate flips (S49):
 *  - 200                          -> Match
 *  - 400 from the code/session
 *    family                       -> Mismatch (collapses into the same
 *                                    uniform 401 as every other miss —
 *                                    expired sessions die there too,
 *                                    like a dead local row)
 *  - 400 anything else            -> typed provider fault (fail closed:
 *    captcha, network, 4xx/5xx      quota, misconfig or argument errors
 *                                   never masquerade as a wrong code).
 *                                   Send and verify both throw it, so
 *                                   the Google head reaches report()
 *                                   from either path (send-side since
 *                                   S50, verify-side now).
 *  - rate-limit head              -> OtpProviderRateLimitedException
 *                                   (retryable 429 rather than 503).
 *
 * PII (hard rule): nothing sent to a provider may be echoed into
 * exceptions or logs — messages carry only HTTP status plus Google's
 * symbolic error head, never free-form error prose (which can echo the
 * phone number), never the code or sessionInfo.
 */
final class FirebasePhoneVerificationProvider implements PhoneVerificationProvider
{
    private const ENDPOINT = 'https://identitytoolkit.googleapis.com/v1';

    /**
     * Wrong-code / dead-session error heads -> Mismatch (uniform 401).
     * Compared against symbolic parts only (message head before the
     * first ':', error.status, error.errors[*].reason) — never free
     * prose. Calibrated by LIVE spike L3 on 2026-10-12: observed heads
     * INVALID_CODE (x5, no lockout within 5) and INVALID_SESSION_INFO
     * (garbage session), both present here — spike doc "Live
     * validation". Gate is test-numbers-only.
     */
    private const CODE_ERROR_CODES = [
        'INVALID_SESSION_INFO',
        'EXPIRED_SESSION_INFO',
        'SESSION_EXPIRED',
        'INVALID_CODE',
        'INVALID_VERIFICATION_CODE',
        'CODE_NOT_FOUND',
    ];

    /**
     * Provider-side back-off heads -> a retryable typed fault instead of
     * a dependency outage. Calibrated by LIVE real-number probes on
     * 2026-10-13 (spike doc RCA): a hammered number returns
     * `TOO_MANY_ATTEMPTS_TRY_LATER` (400), which re-arms on every retry —
     * the client must back off, not hammer, so the envelope says 429.
     */
    private const RATE_LIMIT_ERROR_CODES = [
        'TOO_MANY_ATTEMPTS_TRY_LATER',
        'TOO_MANY_ATTEMPTS',
        'RATE_LIMIT_EXCEEDED',
    ];

    public function __construct(
        private readonly string $name,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 5,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @throws OtpProviderUnavailableException on any rejection or
     *         network failure — never a silent empty result, never a
     *         downgrade (the service fails closed on this before the
     *         challenge row would exist)
     */
    public function send(OtpSendContext $context): OtpSendResult
    {
        $payload = ['phoneNumber' => $context->identifier];
        if ($context->attestation !== null) {
            $payload['recaptchaToken'] = $context->attestation;
        }

        try {
            $response = Http::timeout($this->timeoutSeconds)->post(
                self::ENDPOINT.'/accounts:sendVerificationCode?key='.$this->apiKey,
                $payload,
            );
        } catch (ConnectionException) {
            throw $this->unavailable('network error');
        }

        if (! $response->successful()) {
            throw $this->fault($response);
        }

        $sessionInfo = $response->json('sessionInfo');
        if (! is_string($sessionInfo) || $sessionInfo === '') {
            // A 200 without a session handle cannot be redeemed — treat
            // as a provider fault, not a code the service could store.
            throw $this->unavailable('missing sessionInfo in response');
        }

        return new OtpSendResult(providerRef: $sessionInfo);
    }

    /**
     * @throws OtpProviderUnavailableException when the provider itself
     *         faults (network, captcha, quota, misconfig, 4xx/5xx) — the
     *         typed fault carries the symbolic head, so verify failures
     *         are diagnosable from logs and not just retryable blind.
     *         Rate-limit heads throw the retryable subclass.
     */
    public function verify(OtpVerifyRequest $request): OtpVerifyOutcome
    {
        if ($request->providerRef === null || $request->providerRef === '') {
            // A remote row without a session handle can never match; no
            // request is made and no oracle is produced.
            return OtpVerifyOutcome::Mismatch;
        }

        try {
            $response = Http::timeout($this->timeoutSeconds)->post(
                self::ENDPOINT.'/accounts:signInWithPhoneNumber?key='.$this->apiKey,
                [
                    'sessionInfo' => $request->providerRef,
                    'code' => $request->code,
                ],
            );
        } catch (ConnectionException) {
            throw $this->unavailable('network error');
        }

        if ($response->successful()) {
            return OtpVerifyOutcome::Match;
        }

        if ($response->status() === 400 && $this->isCodeError($response)) {
            return OtpVerifyOutcome::Mismatch;
        }

        // 400 outside the code family (captcha, argument, misconfig) and
        // every 4xx/5xx shape fail closed as a typed fault. This used to
        // return a bare `Unavailable`, which collapsed every provider
        // refusal into one detail-free message — the real cause of a
        // failed redemption (wrong key class, captcha origin, quota) was
        // undiagnosable from our side.
        throw $this->fault($response);
    }

    /**
     * True when the error's symbolic parts name a wrong code or a dead
     * session. Prose is ignored: only the message head before the first
     * ':', error.status and error.errors[*].reason participate.
     */
    private function isCodeError(Response $response): bool
    {
        return array_intersect(self::CODE_ERROR_CODES, $this->symbolicCodes($response)) !== [];
    }

    /**
     * @return list<string>
     */
    private function symbolicCodes(Response $response): array
    {
        $codes = [];

        $message = (string) $response->json('error.message', '');
        if ($message !== '') {
            // Google prefixes errors as 'CODE : prose'; prose may echo
            // the phone number, so only the symbolic head is kept. A head
            // counts only in the FOO_BAR shape: a message with no colon is
            // prose end to end ('API key not valid. Please pass a valid
            // API key.') and must never reach the log as a "code".
            $head = strtoupper(trim(explode(':', $message, 2)[0]));
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $head) === 1) {
                $codes[] = $head;
            }
        }

        $status = (string) $response->json('error.status', '');
        if ($status !== '') {
            $codes[] = strtoupper($status);
        }

        foreach ((array) $response->json('error.errors', []) as $entry) {
            $reason = (string) data_get($entry, 'reason', '');
            if ($reason !== '') {
                $codes[] = strtoupper($reason);
            }
        }

        return array_values(array_filter($codes));
    }

    private function describe(Response $response): string
    {
        $codes = $this->symbolicCodes($response);

        return sprintf(
            'http %d %s',
            $response->status(),
            $codes === [] ? 'unknown' : implode('/', array_unique($codes)),
        );
    }

    private function unavailable(string $reason): OtpProviderUnavailableException
    {
        return new OtpProviderUnavailableException(sprintf(
            'firebase otp provider [%s] unavailable: %s',
            $this->name,
            $reason,
        ));
    }

    /**
     * Typed fault for a rejected response: a rate-limit head becomes the
     * retryable subclass, every other refusal stays a dependency outage.
     * Only symbolic parts travel in the message — never prose, phone,
     * code or session handle.
     */
    private function fault(Response $response): OtpProviderUnavailableException
    {
        $detail = $this->describe($response);

        if (array_intersect(self::RATE_LIMIT_ERROR_CODES, $this->symbolicCodes($response)) !== []) {
            return new OtpProviderRateLimitedException(sprintf(
                'firebase otp provider [%s] rate limited: %s',
                $this->name,
                $detail,
            ));
        }

        return $this->unavailable($detail);
    }
}
