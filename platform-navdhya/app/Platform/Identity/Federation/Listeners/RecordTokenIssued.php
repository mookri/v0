<?php

namespace App\Platform\Identity\Federation\Listeners;

use App\Platform\Identity\Federation\AuthEventRecorder;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;

/**
 * D5: every token issuance hash-chains — authorization code, refresh
 * rotation, client credentials and RFC 8693 exchange all land a
 * token.issued row. The row carries the HMAC-pseudonymous jti
 * reference the exchange's own token.exchanged row shares, so issuance
 * and delegation reconstruct from the chain without any token material.
 *
 * Event fields carry id/client; granted scopes come from the row that
 * persistNewAccessToken wrote just before dispatching (never the token
 * itself).
 */
final class RecordTokenIssued
{
    public function __construct(private readonly AuthEventRecorder $recorder)
    {
    }

    public function handle(AccessTokenCreated $event): void
    {
        $scopes = Passport::token()->newQuery()->whereKey($event->tokenId)->value('scopes');

        $this->recorder->record([
            'event_type' => 'token.issued',
            'decision' => 'allow',
            'subject_id' => $event->userId,
            'actor_client_id' => $event->clientId,
            'token_jti' => $event->tokenId,
            'granted_scopes' => is_array($scopes)
                ? array_values(array_filter($scopes, 'is_string'))
                : null,
            'reason_code' => 'access_token_issued',
        ]);
    }
}
