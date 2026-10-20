<?php

namespace App\Platform\Identity\OAuth\Backchannel;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Identity\OAuth\OidcBackchannelDelivery;
use App\Platform\Identity\OAuth\OidcSessionBinding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Back-channel logout dispatch (OIDC Back-Channel Logout 1.0, SEC-004
 * / A-SEC-004-5): when an OP session ends, every RP bound to its sid
 * with a registered backchannel_logout_uri receives a freshly minted
 * logout_token POST (form body, no redirects followed — a 2xx from the
 * RP itself is the only success).
 *
 * Attempt posture (mirrors A-SEC-003-1's retire-key discipline):
 * one inline attempt at session termination so the common case is
 * synchronous, then a persisted outbox row — re-minting the token per
 * attempt, never storing it — retried by
 * auth:retry-backchannel-logout on a bounded backoff up to
 * MAX_ATTEMPTS, terminal 'failed' beyond. Every attempt outcome is
 * recorded; a client with no endpoint registered is marked notified
 * (nothing to deliver — registration is per-client and fail-closed).
 */
final class BackchannelLogoutDispatcher
{
    /**
     * Bounded inline/retry attempts per delivery: after this the row
     * parks terminal ('failed') — an RP that was down for the whole
     * window is an operational signal (the row keeps last_error),
     * never an unbounded retry loop.
     */
    public const MAX_ATTEMPTS = 5;

    /** @var list<int> seconds before retry #1..#5 */
    private const BACKOFF_SECONDS = [60, 300, 1800, 3600, 7200];

    public function __construct(
        private readonly AuthEventRecorder $audit,
        private readonly BackchannelLogoutTokenMint $mint,
    ) {
    }

    /**
     * Dispatch for every RP bound to $sid that has not been notified
     * yet. $sub is the session's subject (external_id ?? id) resolved
     * by the terminator before logout tore the session down.
     *
     * @return int deliveries created (and attempted inline)
     */
    public function dispatchSession(string $sid, string $sub): int
    {
        if ($sid === '') {
            return 0;
        }

        $bindings = OidcSessionBinding::query()
            ->where('sid', $sid)
            ->whereNull('notified_at')
            ->where('expires_at', '>', now())
            ->orderBy('id')
            ->get();

        $created = 0;

        foreach ($bindings as $binding) {
            $profile = OAuthClientProfile::query()
                ->where('client_id', $binding->client_id)
                ->first();
            $uri = trim((string) ($profile->backchannel_logout_uri ?? ''));

            // No endpoint registered (or no profile left — the client
            // was removed): nothing to deliver to, ever. Mark the
            // binding handled so the enumeration stays idempotent.
            if ($uri === '') {
                $binding->forceFill(['notified_at' => now()])->save();

                continue;
            }

            $scheme = strtolower((string) parse_url($uri, PHP_URL_SCHEME));

            if (! in_array($scheme, ['http', 'https'], true)) {
                // Registration-time config error: visible (report +
                // chain) and never an outbound request to a non-web
                // scheme (the fail-closed rule for outbound targets).
                report(new \RuntimeException(
                    "backchannel_logout_uri for client {$binding->client_id} is not an http(s) URL"
                ));
                $this->recordDelivery($binding, 'error', 'invalid_backchannel_uri');
                $binding->forceFill(['notified_at' => now()])->save();

                continue;
            }

            $delivery = OidcBackchannelDelivery::query()->create([
                'id' => (string) Str::ulid(),
                'sid' => $binding->sid,
                'client_id' => $binding->client_id,
                'logout_uri' => $uri,
                'subject' => $sub,
                'state' => OidcBackchannelDelivery::STATE_PENDING,
                'attempts' => 0,
                'next_attempt_at' => now(),
            ]);
            $binding->forceFill(['notified_at' => now()])->save();

            $this->attempt($delivery);
            $created++;
        }

        return $created;
    }

    /**
     * One delivery attempt: fresh token, one POST, persisted outcome.
     * Public so auth:retry-backchannel-logout drains the outbox
     * through the exact same path as the inline attempt (one code
     * path, one audit shape, one success definition).
     */
    public function attempt(OidcBackchannelDelivery $delivery): void
    {
        $attempts = $delivery->attempts + 1;
        $delivery->attempts = $attempts;

        try {
            $token = $this->mint->mint($delivery->sid, $delivery->subject, $delivery->client_id);

            $response = Http::asForm()
                ->withoutRedirecting()
                ->connectTimeout(2)
                ->timeout(3)
                ->post($delivery->logout_uri, ['logout_token' => $token]);

            if (! $response->successful()) {
                throw new \RuntimeException('HTTP status '.$response->status());
            }

            $delivery->forceFill([
                'state' => OidcBackchannelDelivery::STATE_DELIVERED,
                'delivered_at' => now(),
                'last_error' => null,
                'next_attempt_at' => now(),
            ])->save();

            $this->recordDelivery($delivery, 'allow', 'delivered');
        } catch (\Throwable $e) {
            $exhausted = $attempts >= self::MAX_ATTEMPTS;

            $delivery->forceFill([
                'state' => $exhausted
                    ? OidcBackchannelDelivery::STATE_FAILED
                    : OidcBackchannelDelivery::STATE_PENDING,
                'last_error' => substr($e->getMessage(), 0, 500),
                'next_attempt_at' => $exhausted
                    ? $delivery->next_attempt_at ?? now()
                    : now()->addSeconds(self::BACKOFF_SECONDS[min($attempts - 1, 4)]),
            ])->save();

            $this->recordDelivery(
                $delivery,
                'error',
                $exhausted ? 'attempts_exhausted' : 'transport_error'
            );
        }
    }

    /**
     * Delivery-outcome chain record. Telemetry on top of the
     * session.logout decision (which stays fail-closed in the
     * terminator): the persisted row already carries the state, so an
     * audit-chain outage here is reported, never allowed to break a
     * logout that has already invalidated the session.
     *
     * @param  OidcSessionBinding|OidcBackchannelDelivery  $record
     * @param  'allow'|'error'  $decision
     */
    private function recordDelivery(OidcSessionBinding|OidcBackchannelDelivery $record, string $decision, string $reason): void
    {
        try {
            $this->audit->record([
                'event_type' => 'session.backchannel_logout',
                'decision' => $decision,
                'session_id' => $record->sid,
                'actor_client_id' => $record->client_id,
                'source_audience' => 'federation',
                'risk_tier' => 'T0',
                'reason_code' => $reason,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
