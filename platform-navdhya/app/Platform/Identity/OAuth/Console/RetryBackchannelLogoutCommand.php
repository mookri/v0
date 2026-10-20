<?php

namespace App\Platform\Identity\OAuth\Console;

use App\Platform\Identity\OAuth\Backchannel\BackchannelLogoutDispatcher;
use App\Platform\Identity\OAuth\OidcBackchannelDelivery;
use Illuminate\Console\Command;

/**
 * auth:retry-backchannel-logout (SEC-004 / A-SEC-004-5): drain the
 * back-channel logout outbox — every pending delivery whose backoff
 * window has opened, re-attempted through the SAME dispatcher path as
 * the inline attempt (fresh token re-minted per attempt through the
 * signing custody, one POST, persisted outcome) until delivered or
 * terminal at BackchannelLogoutDispatcher::MAX_ATTEMPTS.
 *
 * Failure-audit posture mirrors auth:retire-signing-key
 * (A-SEC-003-1): every attempt outcome lands on the row (attempts,
 * last_error) AND in the hash-chained chain — a delivery that never
 * landed is visible data, never a silent drop.
 */
final class RetryBackchannelLogoutCommand extends Command
{
    protected $signature = 'auth:retry-backchannel-logout
        {--force : Attempt every pending delivery regardless of its backoff window}
        {--limit=200 : Maximum deliveries to attempt in this run}';

    protected $description = 'Retry pending OIDC back-channel logout deliveries';

    public function handle(BackchannelLogoutDispatcher $dispatcher): int
    {
        $limit = max(1, (int) $this->option('limit'));

        // Env-parity guard (live fan-out drill, A-SEC-004-6): the
        // re-minted token carries config('federation.issuer') as its
        // iss — a retry process launched without the serving issuer's
        // AUTH_* environment mints a mismatched iss and EVERY RP
        // rejects the token (uniform 400), burning backoff windows.
        // Print the issuer so an operator sees exactly what the RP
        // will byte-compare against.
        $this->info('federation.issuer: '.config('federation.issuer'));

        $query = OidcBackchannelDelivery::query()
            ->where('state', OidcBackchannelDelivery::STATE_PENDING)
            ->where('attempts', '<', BackchannelLogoutDispatcher::MAX_ATTEMPTS)
            ->orderBy('next_attempt_at')
            ->limit($limit);

        if (! $this->option('force')) {
            $query->where('next_attempt_at', '<=', now());
        }

        $due = $query->get();

        $delivered = 0;
        $stillPending = 0;
        $exhausted = 0;

        foreach ($due as $delivery) {
            $dispatcher->attempt($delivery);
            $delivery->refresh();

            match ($delivery->state) {
                OidcBackchannelDelivery::STATE_DELIVERED => $delivered++,
                OidcBackchannelDelivery::STATE_FAILED => $exhausted++,
                default => $stillPending++,
            };
        }

        $this->info(sprintf(
            'Attempted %d delivery(ies): %d delivered, %d still pending, %d terminal.',
            $due->count(),
            $delivered,
            $stillPending,
            $exhausted
        ));

        return self::SUCCESS;
    }
}
