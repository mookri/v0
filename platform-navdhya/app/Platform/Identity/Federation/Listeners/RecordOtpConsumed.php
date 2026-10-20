<?php

namespace App\Platform\Identity\Federation\Listeners;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Otp\OtpChallengeConsumed;

/**
 * D5: OTP redemption is an authentication decision — it belongs in the
 * chain. The challenge's identifier is a raw phone/email; the recorder
 * HMAC-pseudonymizes it as the subject ref, so the clear identifier
 * never reaches the audit table (same rule as every other event).
 */
final class RecordOtpConsumed
{
    public function __construct(private readonly AuthEventRecorder $recorder)
    {
    }

    public function handle(OtpChallengeConsumed $event): void
    {
        $this->recorder->record([
            'event_type' => 'otp.challenge_consumed',
            'decision' => 'allow',
            'subject_id' => $event->identifier,
            'tenant_ref' => $event->tenantId,
            'grant_type' => 'otp:'.$event->purpose,
            'reason_code' => 'otp_verified',
        ]);
    }
}
