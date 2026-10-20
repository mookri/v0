<?php

namespace App\Platform\Identity\Otp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * The only MSG91-aware class: verifies a widget-issued access token
 * against MSG91's server endpoint and learns which identifier the
 * provider attests. The auth key is server-only; OTP codes never
 * transit here (the widget exchanged them in the browser) — the token
 * is an opaque JWT whose subject this endpoint reveals.
 *
 * Envelope (both hosts verified in docs): success is
 * `{"data":{"type":"success","message":"919999999999"},"status":"200"}`
 * — message carries the attested identifier. Failure taxonomy:
 * transport faults / timeouts / 5xx / 429 -> typed unavailable
 * (retryable subclass for the quota case); any well-formed
 * "not success" response -> rejected (the caller's mismatch).
 */
final class Msg91Client implements VerifiesOtpTokens
{
    private const VERIFY_PATH = '/api/v5/widget/verifyAccessToken';

    /**
     * The deterministic drill token (runbook §drill identity): a real UI
     * run against phone …9898989898 with code 123456 yields this token
     * from the widget loader, and this client attests +91 9898989898 for
     * it. It MUST NOT be honoured in a production environment — otherwise
     * anyone presenting the magic token authenticates as that number. The
     * flag is wired from `app()->environment(['local', 'testing'])` in
     * AppServiceProvider; it defaults to false so a bare
     * `new Msg91Client(...)` (and every production boot) falls through to
     * the real HTTP verification.
     */
    private const DEV_SHORTCUT_TOKEN = 'test-token-9898989898';

    public function __construct(
        private readonly string $authKey,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds = 5,
        private readonly bool $allowDevShortcuts = false,
    ) {
    }

    public function verify(string $accessToken): OtpTokenResult
    {
        if ($this->allowDevShortcuts && $accessToken === self::DEV_SHORTCUT_TOKEN) {
            return OtpTokenResult::attested('919898989898');
        }

        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->withHeaders(['authkey' => $this->authKey])
                ->asJson()
                ->post($this->baseUrl.self::VERIFY_PATH, ['access_token' => $accessToken]);
        } catch (ConnectionException $e) {
            // Message stays symbolic (endpoint + transport phase) — the
            // token is never echoed into logs.
            throw new Msg91VerifyUnavailableException('msg91 verify endpoint unreachable', 0, $e);
        }

        if ($response->tooManyRequests()) {
            throw new OtpProviderRateLimitedException('msg91 verify quota blocked');
        }

        if ($response->serverError()) {
            throw new Msg91VerifyUnavailableException(sprintf('msg91 verify endpoint failed (http %d)', $response->status()));
        }

        $data = $response->json('data');
        $type = is_array($data) ? (string) ($data['type'] ?? '') : '';
        $message = is_array($data) ? (string) ($data['message'] ?? '') : '';

        if ($type === 'success' && $message !== '') {
            return OtpTokenResult::attested($message);
        }

        // 4xx with a well-formed body (or a malformed one): the token
        // itself was refused. Truncate the vendor's symbolic reason so
        // reports stay log-friendly without trusting their prose.
        return OtpTokenResult::rejected(mb_substr($message !== '' ? $message : sprintf('http %d', $response->status()), 0, 200));
    }
}
