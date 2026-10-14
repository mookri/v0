<?php

namespace App\Platform\Identity\Otp;

/**
 * Phone-OTP provider port (Verification Fabric S46). The challenge state
 * machine — rate caps, supersession, TTL, attempt counting, uniform-401
 * redemption — stays in the caller and never changes; only send and
 * match are delegated, so swapping providers is an adapter + config
 * flip (ADR-002 recipe 1).
 */
interface PhoneVerificationProvider
{
    /**
     * Stable provider name persisted on the challenge row (`provider`)
     * so verification dispatches on the row's own provenance — hybrid
     * fleets and per-row audit survive any later swap.
     */
    public function name(): string;

    /**
     * Prepare the send before the challenge row exists. Local providers
     * return the plaintext code (the service hashes it into the row and
     * the drill log); remote providers return an opaque provider_ref
     * (e.g. Identity Toolkit sessionInfo) and never expose a code.
     */
    public function send(OtpSendContext $context): OtpSendResult;

    /**
     * Match one redemption attempt. Unavailable means the provider is
     * down/quota-blocked and must fail closed as a typed dependency
     * error — never as a mismatch oracle and never as a silent
     * downgrade to a channel that cannot actually deliver. An adapter
     * may instead throw OtpProviderUnavailableException (or its
     * retryable OtpProviderRateLimitedException subclass) when it holds
     * a provider detail worth logging; callers treat the two as the same
     * fail-closed outcome.
     */
    public function verify(OtpVerifyRequest $request): OtpVerifyOutcome;
}
