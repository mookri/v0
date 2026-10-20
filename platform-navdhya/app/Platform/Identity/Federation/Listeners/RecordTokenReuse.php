<?php

namespace App\Platform\Identity\Federation\Listeners;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\OAuth\TokenReuseDetected;

/**
 * D4/D5 bridge: refresh-token reuse is both a family-kill (D4) and a
 * mandatory audit event (D5). The recorder stores only pseudonymous
 * refs — the raw user/client ids from the event never persist in the
 * clear beyond this process.
 */
final class RecordTokenReuse
{
    public function __construct(private readonly AuthEventRecorder $recorder)
    {
    }

    public function handle(TokenReuseDetected $event): void
    {
        $this->recorder->record([
            'event_type' => 'session.reuse_detected',
            'decision' => 'deny',
            'subject_id' => $event->userId,
            'actor_client_id' => $event->clientId,
            'grant_type' => 'refresh_token',
            'reason_code' => 'refresh_token_reuse',
        ]);
    }
}
