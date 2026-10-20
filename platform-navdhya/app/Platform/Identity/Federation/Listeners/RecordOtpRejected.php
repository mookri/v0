<?php

namespace App\Platform\Identity\Federation\Listeners;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Otp\OtpChallengeRejected;

/**
 * D5: failed OTP redemptions are authorization denials and belong in
 * the chain (abuse/risk forensics). The challenge id is already an
 * internal ULID — pseudonymous by construction, stored as the session
 * ref so consecutive failures on one challenge correlate without
 * exposing any identifier.
 */
final class RecordOtpRejected
{
    public function __construct(private readonly AuthEventRecorder $recorder)
    {
    }

    public function handle(OtpChallengeRejected $event): void
    {
        $this->recorder->record([
            'event_type' => 'otp.challenge_rejected',
            'decision' => 'deny',
            'session_id' => $event->challengeId,
            'tenant_ref' => $event->tenantId,
            'grant_type' => 'otp:'.$event->purpose,
            'reason_code' => 'otp_redeem_failed',
        ]);
    }
}
