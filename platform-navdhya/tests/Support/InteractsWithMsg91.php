<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Http;

trait InteractsWithMsg91
{
    /**
     * Generate a deterministic fake JWT token for an identifier.
     */
    protected function msg91TokenFor(string $identifier): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => $identifier, 'iss' => 'msg91-fake'])), '+/', '-_'), '=');
        return 'fake.jwt.' . $payload;
    }

    /**
     * Setup Http::fake for MSG91 widget token verification endpoint.
     *
     * @param array<string, string|null>|null $tokenMap Optional explicit mapping: token => identifier (or null for rejection)
     */
    protected function fakeMsg91(?array $tokenMap = null): void
    {
        Http::fake([
            '*/api/v5/widget/verifyAccessToken' => function ($request) use ($tokenMap) {
                $body = $request->data();
                $token = $body['access_token'] ?? null;

                if (! is_string($token) || $token === '') {
                    return Http::response([
                        'data' => ['type' => 'error', 'message' => 'invalid_token'],
                        'status' => '400',
                    ], 400);
                }

                if ($tokenMap !== null) {
                    if (array_key_exists($token, $tokenMap)) {
                        $mapped = $tokenMap[$token];
                        if ($mapped === null) {
                            return Http::response([
                                'data' => ['type' => 'error', 'message' => 'token_rejected'],
                                'status' => '401',
                            ], 401);
                        }
                        if ($mapped === '500') {
                            return Http::response('Internal Server Error', 500);
                        }
                        if ($mapped === '429') {
                            return Http::response('Too Many Requests', 429);
                        }
                        return Http::response([
                            'data' => ['type' => 'success', 'message' => $mapped],
                            'status' => '200',
                        ], 200);
                    }
                    return Http::response([
                        'data' => ['type' => 'error', 'message' => 'token_not_found'],
                        'status' => '401',
                    ], 401);
                }

                // If deterministic fake token: fake.jwt.<base64url-payload>
                $parts = explode('.', $token);
                if (count($parts) === 3 && $parts[0] === 'fake') {
                    $payloadJson = base64_decode(strtr($parts[2], '-_', '+/'));
                    $decoded = json_decode($payloadJson, true);
                    if (is_array($decoded) && isset($decoded['sub'])) {
                        return Http::response([
                            'data' => ['type' => 'success', 'message' => (string) $decoded['sub']],
                            'status' => '200',
                        ], 200);
                    }
                }

                return Http::response([
                    'data' => ['type' => 'error', 'message' => 'token_invalid'],
                    'status' => '401',
                ], 401);
            },
        ]);
    }
}
