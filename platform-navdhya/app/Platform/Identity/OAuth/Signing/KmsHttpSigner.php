<?php

namespace App\Platform\Identity\OAuth\Signing;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * SignsTokenRemotely over the KMS shim's HTTP contract:
 *
 *   POST {endpoint}   Content-Type: application/json
 *                     Authorization: Bearer <auth token>   (when configured)
 *   {"algorithm": "RS256", "signing_input": "<base64url>"}
 *        ->
 *   200 {"signature": "<base64url(RSASSA-PKCS1-v1_5 SHA-256 signature)>"}
 *
 * RS256's wire format is exactly what cloud KMS asymmetricSign
 * operations return for RSA_SIGN_PKCS1_1_5_SHA_256 (DER/ASN.1), so the
 * shim is a thin base64url transcoder in front of its custody — no key
 * material, no signing logic, nothing that could quietly change the
 * signature on the way through. Any failure is loud (a typed
 * exception): a mint that cannot be signed fails, it is never
 * downgraded to a different key or a different algorithm.
 */
final class KmsHttpSigner implements SignsTokenRemotely
{
    public function __construct(
        private readonly string $endpoint,
        private readonly string $authToken,
        private readonly int $timeoutSeconds,
    ) {
    }

    public function sign(string $signingInput): string
    {
        if ($this->endpoint === '') {
            throw new RemoteSigningException('KMS signing endpoint (federation.signing.kms.endpoint) is not configured');
        }

        $request = Http::acceptJson()
            ->asJson()
            ->timeout(max(1, $this->timeoutSeconds));

        if ($this->authToken !== '') {
            $request = $request->withToken($this->authToken);
        }

        try {
            $response = $request->post($this->endpoint, [
                'algorithm' => 'RS256',
                'signing_input' => self::base64UrlEncode($signingInput),
            ]);
        } catch (Throwable $e) {
            throw new RemoteSigningException('KMS signing endpoint unreachable: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new RemoteSigningException("KMS signing endpoint returned HTTP {$response->status()}");
        }

        $signature = $response->json('signature');

        if (! is_string($signature) || $signature === '') {
            throw new RemoteSigningException('KMS signing endpoint response has no usable signature field');
        }

        $raw = self::base64UrlDecode($signature);

        if ($raw === null || $raw === '') {
            throw new RemoteSigningException('KMS signing endpoint returned a signature that is not valid base64url');
        }

        return $raw;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): ?string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
