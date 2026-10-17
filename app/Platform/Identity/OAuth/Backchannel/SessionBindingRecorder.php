<?php

namespace App\Platform\Identity\OAuth\Backchannel;

use App\Platform\Identity\OAuth\OidcSessionBinding;
use Illuminate\Support\Str;

/**
 * Records the (sid, client, user) binding at openid authorization time
 * (SEC-004 / A-SEC-004-5) — the enumeration source back-channel
 * logout dispatch reads when the OP session ends.
 *
 * Availability posture: an authorization must never fail because the
 * enumeration side-table could not be written (the security-critical
 * artifact is the code/token chain itself; a missed binding only skips
 * one RP's best-effort logout push under an outage that would take the
 * OAuth tables with it anyway). Failures are reported, never swallowed
 * silently, and the delivery/retry machinery only ever sees rows that
 * exist.
 */
final class SessionBindingRecorder
{
    /**
     * Refresh lineage the binding must outlive (Passport refresh TTL,
     * AppServiceProvider: refreshTokensExpireIn 30 d) — a binding that
     * expires before the lineage could orphan a still-live refresh
     * chain from its logout notification.
     */
    private const LINEAGE_DAYS = 30;

    public static function record(string $sid, string $clientId, int $userId): void
    {
        if ($sid === '' || $clientId === '') {
            return;
        }

        try {
            $now = now();

            OidcSessionBinding::query()->upsert(
                [[
                    'id' => (string) Str::ulid(),
                    'sid' => $sid,
                    'client_id' => $clientId,
                    'user_id' => $userId,
                    'expires_at' => $now->copy()->addDays(self::LINEAGE_DAYS)->toDateTimeString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['sid', 'client_id'],
                // Repeat authorizations refresh the lineage window but
                // never clear notified_at: an in-flight dispatch stays
                // marked (the session it belonged to is ending/ended).
                ['user_id', 'expires_at', 'updated_at']
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
