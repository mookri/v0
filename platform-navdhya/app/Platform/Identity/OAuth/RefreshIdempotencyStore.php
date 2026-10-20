<?php

namespace App\Platform\Identity\OAuth;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Crypt;

/**
 * D4 lost-response recovery (ADR-0020): a refresh exchange that already
 * rotated may be retried with the SAME client + Idempotency-Key + request
 * fingerprint inside federation.refresh_retry_window_seconds — the retry
 * recovers the ORIGINAL response instead of presenting a consumed token
 * (which is reuse). This is recovery, not a replay grace period: any
 * mismatch (key, client, fingerprint) or an elapsed window falls through
 * to normal validation, where a consumed token is reuse and kills the
 * lineage.
 *
 * Contract:
 *  - Records are keyed by HMAC(identity.hmac_secret) over the client's
 *    Idempotency-Key plus a fingerprint of client_id + refresh_token +
 *    scope — no secret, no recovery (fail closed); a malformed key
 *    disables recovery rather than failing the exchange.
 *  - The recovered body is encrypted at rest (APP_KEY) and stored
 *    first-write-wins (`add`): retries can neither read foreign entries
 *    nor extend the window they recover inside.
 *  - Every store operation is guarded — an unavailable store disables
 *    recovery only. It can never bypass reuse detection (D4 failure
 *    matrix: never bypass a security check).
 */
final class RefreshIdempotencyStore
{
    private const KEY_PREFIX = 'authz:refresh-idem:';

    private const KEY_PATTERN = '/^[A-Za-z0-9._:-]{8,128}$/';

    /**
     * The store is injected (not taken from the facade) so an outage can
     * be scoped to this component in tests: a dead repository must
     * disable recovery here without taking throttling or sessions down
     * with it.
     */
    public function __construct(private readonly Repository $cache)
    {
    }

    /**
     * Record the original response of a successful rotation.
     *
     * @param  array{client_id?: string, refresh_token?: string, scope?: string}  $params
     */
    public function record(string $idempotencyKey, array $params, string $body): void
    {
        $key = $this->recordKey($idempotencyKey, $params);
        if ($key === null) {
            return;
        }

        $window = (int) config('federation.refresh_retry_window_seconds', 60);
        if ($window <= 0) {
            return;
        }

        try {
            // add(): first-write-wins — a replay can never extend the
            // window it is recovering inside.
            $this->cache->add($key, Crypt::encryptString($body), $window);
        } catch (\Throwable) {
            // Store down: recovery unavailable; the exchange itself is
            // never affected (D4: the accelerator is not the authority).
        }
    }

    /**
     * Recover the original response for an identical retry, or null on
     * every miss (no record, window elapsed, store down, no signing
     * secret, malformed key) — all of which fall through to normal
     * validation and its reuse detection.
     *
     * @param  array{client_id?: string, refresh_token?: string, scope?: string}  $params
     */
    public function recover(string $idempotencyKey, array $params): ?string
    {
        $key = $this->recordKey($idempotencyKey, $params);
        if ($key === null) {
            return null;
        }

        try {
            $encrypted = $this->cache->get($key);
            if (! is_string($encrypted)) {
                return null;
            }

            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array{client_id?: string, refresh_token?: string, scope?: string}  $params
     */
    private function recordKey(string $idempotencyKey, array $params): ?string
    {
        $secret = (string) config('identity.hmac_secret', '');

        if ($secret === '' || ! preg_match(self::KEY_PATTERN, $idempotencyKey)) {
            return null;
        }

        $fingerprint = hash('sha256', implode("\n", [
            (string) ($params['client_id'] ?? ''),
            (string) ($params['refresh_token'] ?? ''),
            (string) ($params['scope'] ?? ''),
        ]));

        return self::KEY_PREFIX.hash_hmac('sha256', $idempotencyKey."\n".$fingerprint, $secret);
    }
}
